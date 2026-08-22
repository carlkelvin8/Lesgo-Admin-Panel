<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class PasswordHistory extends Model
{
    protected $table = 'password_history';

    protected $fillable = [
        'user_id',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the user that owns this password record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if a given password was used in the user's last N passwords.
     */
    public function wasUsedRecently(int $userId, string $password, int $limit = 5): bool
    {
        return self::isPasswordReused($userId, $password, $limit);
    }

    /**
     * Record a new password in the history.
     */
    public static function recordPassword(int $userId, string $password): static
    {
        return static::create([
            'user_id' => $userId,
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Check if the password matches any of the user's last N passwords.
     */
    public static function isPasswordReused(int $userId, string $password, int $limit = 5): bool
    {
        $recentPasswords = static::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('password');

        foreach ($recentPasswords as $hashedPassword) {
            if (Hash::check($password, $hashedPassword)) {
                return true;
            }
        }

        return false;
    }
}
