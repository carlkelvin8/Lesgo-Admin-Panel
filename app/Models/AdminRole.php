<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminRole extends Model
{
    private static ?Collection $resolvedDefinitions = null;

    private const DEFINITIONS_CACHE_KEY = 'admin:role_definitions:v2';

    protected $table = 'admin_access_roles';

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
        if (static::$resolvedDefinitions instanceof Collection) {
            return static::$resolvedDefinitions;
        }

        // Shared cache so Octane/long-lived workers see updates immediately.
        try {
            $cached = Cache::get(self::DEFINITIONS_CACHE_KEY);
            if ($cached instanceof Collection && $cached->isNotEmpty()) {
                return static::$resolvedDefinitions = $cached;
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            if (Schema::hasTable('admin_access_roles')) {
                $roles = static::query()->get()->keyBy('key');

                if ($roles->isNotEmpty()) {
                    try { Cache::put(self::DEFINITIONS_CACHE_KEY, $roles, 300); } catch (Throwable $e) { report($e); }
                    return static::$resolvedDefinitions = $roles;
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return static::$resolvedDefinitions = static::configuredDefinitions();
    }

    public static function definition(?string $key): ?self
    {
        return $key ? static::definitions()->get($key) : null;
    }

    public static function forgetDefinitionCache(): void
    {
        static::$resolvedDefinitions = null;
        try { Cache::forget(self::DEFINITIONS_CACHE_KEY); } catch (Throwable $e) { report($e); }
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    private static function configuredDefinitions(): Collection
    {
        return collect(config('admin.roles', []))->map(
            fn (array $definition, string $key) => (new static)->forceFill([
                'key' => $key,
                'label' => $definition['label'],
                'permissions' => $definition['permissions'],
                'is_protected' => $key === 'super_admin',
            ]),
        );
    }
}
