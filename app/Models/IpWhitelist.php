<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IpWhitelist extends Model
{
    protected $table = 'ip_whitelist';

    protected $fillable = [
        'ip_address', 'ip_range', 'type', 'description', 'is_active',
        'expires_at', 'created_by', 'metadata',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'expires_at' => 'datetime', 'metadata' => 'array'];
    }

    public function scopeEffective(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
