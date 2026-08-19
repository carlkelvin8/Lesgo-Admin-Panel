<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseCloudMessaging
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function send(Notification $notification): string
    {
        $user = $notification->user;

        if (blank($user?->fcm_token)) {
            throw new RuntimeException('The recipient does not have an FCM registration token.');
        }

        $credentials = $this->credentials();
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);

        if (blank($projectId)) {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        $data = collect($notification->data ?? [])
            ->mapWithKeys(fn ($value, $key) => [(string) $key => is_scalar($value) ? (string) $value : json_encode($value)])
            ->all();

        $response = Http::withToken($this->accessToken($credentials))
            ->acceptJson()
            ->timeout(20)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $user->fcm_token,
                    'notification' => [
                        'title' => $notification->title,
                        'body' => $notification->body,
                    ],
                    'data' => $data,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('FCM delivery failed: '.$response->body());
        }

        return $response->json('name') ?: 'fcm-http-v1';
    }

    private function credentials(): array
    {
        $encoded = config('services.firebase.credentials_base64');

        if (filled($encoded)) {
            $decoded = base64_decode($encoded, true);
            $credentials = $decoded === false ? null : json_decode($decoded, true);
        } else {
            $path = config('services.firebase.credentials');
            $credentials = filled($path) && is_readable($path)
                ? json_decode(file_get_contents($path), true)
                : null;
        }

        if (! is_array($credentials) || blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
            throw new RuntimeException('Valid Firebase service-account credentials are not configured.');
        }

        return $credentials;
    }

    private function accessToken(array $credentials): string
    {
        $cacheKey = 'firebase-access-token:'.hash('sha256', $credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $tokenUri = $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token';
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => $tokenUri,
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsigned = $header.'.'.$claims;

            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign the Firebase service-account assertion.');
            }

            $assertion = $unsigned.'.'.$this->base64Url($signature);
            $response = Http::asForm()->timeout(20)->post($tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new RuntimeException('Unable to obtain a Firebase OAuth access token.');
            }

            return $response->json('access_token');
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
