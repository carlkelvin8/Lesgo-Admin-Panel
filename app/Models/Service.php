<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'code',
        'name',
        'description',
        'base_fare',
        'per_km_rate',
        'per_minute_rate',
        'minimum_fare',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
