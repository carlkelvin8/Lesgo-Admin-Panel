<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminRole extends Model
{
    private const CACHE_KEY = 'admin_roles.definitions';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'label',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_protected' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetDefinitionCache());
        static::deleted(fn () => static::forgetDefinitionCache());
    }

    public static function definitions(): Collection
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->get()->keyBy('key'),
        );
    }

    public static function definition(?string $key): ?self
    {
        return $key ? static::definitions()->get($key) : null;
    }

    public static function forgetDefinitionCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }
}
