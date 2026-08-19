<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuritySetting extends Model
{
    protected $fillable = [
        'setting_key', 'setting_value', 'data_type', 'description', 'category',
        'is_sensitive', 'requires_restart', 'updated_by', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'requires_restart' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->data_type) {
            'boolean' => filter_var($setting->setting_value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->setting_value,
            'json' => json_decode($setting->setting_value, true),
            default => $setting->setting_value,
        };
    }
}
