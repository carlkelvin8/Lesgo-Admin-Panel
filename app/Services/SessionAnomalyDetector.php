<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class SessionAnomalyDetector
{
    /**
     * Check for session anomalies on login.
     */
    public function detect(array $currentLogin, array $previousLogin = null): array
    {
        $anomalies = [];

        if ($previousLogin) {
            $travelAnomaly = $this->checkImpossibleTravel($currentLogin, $previousLogin);
            if ($travelAnomaly) {
                $anomalies[] = $travelAnomaly;
            }

            $countryAnomaly = $this->checkCountryChange($currentLogin, $previousLogin);
            if ($countryAnomaly) {
                $anomalies[] = $countryAnomaly;
            }
        }

        $timeAnomaly = $this->checkUnusualTime($currentLogin);
        if ($timeAnomaly) {
            $anomalies[] = $timeAnomaly;
        }

        return $anomalies;
    }

    /**
     * Check if login is at an unusual hour (3 AM - 5 AM).
     */
    protected function checkUnusualTime(array $login): ?array
    {
        $timestamp = $login['timestamp'] ?? now()->toDateTimeString();
        $hour = (int) date('G', is_string($timestamp) ? strtotime($timestamp) : $timestamp->timestamp);

        if ($hour >= 3 && $hour <= 5) {
            return [
                'type' => 'unusual_time',
                'severity' => 'low',
                'message' => "Login occurred at an unusual hour ({$hour}:00).",
            ];
        }

        return null;
    }

    /**
     * Check if the login IP is from a different country than the previous login.
     */
    protected function checkCountryChange(array $current, array $previous): ?array
    {
        $currentCountry = $this->getCountryFromIp($current['ip']);
        $previousCountry = $this->getCountryFromIp($previous['ip']);

        if ($currentCountry && $previousCountry && $currentCountry !== $previousCountry) {
            return [
                'type' => 'country_change',
                'severity' => 'medium',
                'message' => "Login country changed from {$previousCountry} to {$currentCountry}.",
            ];
        }

        return null;
    }

    /**
     * Detect impossible travel: different countries within 1 hour.
     */
    protected function checkImpossibleTravel(array $current, array $previous): ?array
    {
        $currentCountry = $this->getCountryFromIp($current['ip']);
        $previousCountry = $this->getCountryFromIp($previous['ip']);

        if (!$currentCountry || !$previousCountry) {
            return null;
        }

        if ($currentCountry === $previousCountry) {
            return null;
        }

        $timeDiff = strtotime($current['timestamp'] ?? now()) - strtotime($previous['timestamp']);

        if (abs($timeDiff) <= 3600) {
            return [
                'type' => 'impossible_travel',
                'severity' => 'high',
                'message' => "Login from {$previousCountry} then {$currentCountry} within 1 hour (impossible travel).",
            ];
        }

        return null;
    }

    /**
     * Resolve an IP address to a country code.
     * Uses a simple GeoIP lookup via ip-api.com (cached for 24h).
     */
    protected function getCountryFromIp(string $ip): ?string
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'LOCAL';
        }

        $cacheKey = "geoip:{$ip}";

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                $url = "http://ip-api.com/json/{$ip}?fields=countryCode";
                $response = file_get_contents($url);

                if ($response === false) {
                    return null;
                }

                $data = json_decode($response, true);

                return $data['countryCode'] ?? null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }
}
