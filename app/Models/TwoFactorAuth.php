<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;

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
            'backup_codes' => 'array',
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

    public static function forUser(int $userId, bool $enabledOnly = false): ?static
    {
        $fallback = null;
        foreach (['two_factor_auths', 'two_factor_auth'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $record = (new static)->setTable($table)->newQuery()->where('user_id', $userId)->where('method', 'totp')->first();
            if ($record?->is_enabled) {
                return $record;
            }
            $fallback ??= $record;
        }

        return $enabledOnly ? null : $fallback;
    }

    public function generateTotpSecret(): string
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $this->update(['secret' => Crypt::encryptString($secret)]);

        return $secret;
    }

    public function getTotpSecret(): ?string
    {
        if ($this->secret === null) {
            return null;
        }

        try {
            return Crypt::decryptString($this->secret);
        } catch (DecryptException) {
            return null;
        }
    }

    public function getQrCodeUrl(): string
    {
        $secret = $this->getTotpSecret();
        $user = $this->user;
        $google2fa = app(Google2FA::class);

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

        $google2fa = app(Google2FA::class);
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
            'recovery_codes' => null,
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
        return DB::transaction(function () use ($user) {
            $updated = 0;
            foreach (['two_factor_auths', 'two_factor_auth'] as $table) {
                if (Schema::hasTable($table)) {
                    $updated += (new static)->setTable($table)->newQuery()->where('user_id', $user->id)
                        ->where('method', 'totp')->update(['is_enabled' => false]);
                }
            }

            return $updated > 0;
        });
    }
}
