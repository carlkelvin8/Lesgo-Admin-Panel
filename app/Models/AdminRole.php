<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminRole extends Model
{
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

    public static function definitions(): Collection
    {
        try {
            if (Schema::hasTable('admin_access_roles')) {
                $roles = static::query()->get()->keyBy('key');

                if ($roles->isNotEmpty()) {
                    return $roles;
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return static::configuredDefinitions();
    }

    public static function definition(?string $key): ?self
    {
        return $key ? static::definitions()->get($key) : null;
    }

    public static function forgetDefinitionCache(): void
    {
        // Kept for callers on older deployments; definitions are read fresh now.
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
