<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'secret',
        'backup_codes',
        'phone_number',
        'is_enabled',
        'enabled_at',
        'last_used_at',
        'recovery_codes',
        'metadata',
    ];

    protected $hidden = [
        'secret',
        'backup_codes',
        'recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'enabled_at' => 'datetime',
            'last_used_at' => 'datetime',
            'recovery_codes' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled && $this->method === 'totp';
    }

    public function generateTotpSecret(): string
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $this->update(['secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)]);

        return $secret;
    }

    public function getTotpSecret(): ?string
    {
        if ($this->secret === null) {
            return null;
        }

        return \Illuminate\Support\Facades\Crypt::decryptString($this->secret);
    }

    public function getQrCodeUrl(): string
    {
        $secret = $this->getTotpSecret();
        $user = $this->user;
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        return $google2fa->getQRCodeUrl(
            'LesGo Admin',
            $user->email,
            $secret
        );
    }

    public function verifyCode(string $code): bool
    {
        $secret = $this->getTotpSecret();

        if ($secret === null) {
            return false;
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $valid = $google2fa->verifyKey($secret, $code);

        if ($valid) {
            $this->update(['last_used_at' => now()]);
        }

        return $valid;
    }

    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        $hashedCodes = array_map(fn ($code) => hash('sha256', $code), $codes);

        $this->update([
            'backup_codes' => $hashedCodes,
            'recovery_codes' => $codes,
        ]);

        return $codes;
    }

    public function useBackupCode(string $code): bool
    {
        $hashed = hash('sha256', strtoupper($code));
        $codes = $this->backup_codes ?? [];

        if (! in_array($hashed, $codes)) {
            return false;
        }

        $codes = array_diff($codes, [$hashed]);
        $this->update(['backup_codes' => array_values($codes)]);

        return true;
    }

    public static function enableForUser(User $user): static
    {
        return static::updateOrCreate(
            ['user_id' => $user->id, 'method' => 'totp'],
            ['is_enabled' => true, 'enabled_at' => now()]
        );
    }

    public static function disableForUser(User $user): bool
    {
        return static::where('user_id', $user->id)
            ->where('method', 'totp')
            ->update(['is_enabled' => false]) > 0;
    }
}
