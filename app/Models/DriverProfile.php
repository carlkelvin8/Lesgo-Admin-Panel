<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'partner_id',
        'status',
        'rating',
        'total_trips',
        'license_number',
        'license_expiry_date',
        'last_latitude',
        'last_longitude',
        'vehicle_type',
        'vehicle_make',
        'vehicle_model',
        'vehicle_color',
        'vehicle_plate_number',
        'package_tier',
        'documents',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:2',
            'license_expiry_date' => 'date',
            'documents' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
