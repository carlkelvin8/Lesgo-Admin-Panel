<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'legal_name',
        'slug',
        'business_type',
        'status',
        'logo_url',
        'cover_image_url',
        'description',
        'category',
        'tags',
        'cuisine_types',
        'rating',
        'total_reviews',
        'delivery_fee',
        'min_order_amount',
        'estimated_delivery_minutes',
        'is_open',
        'is_featured',
        'accepts_online_payment',
        'opening_hours',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'cuisine_types' => 'array',
            'opening_hours' => 'array',
            'is_open' => 'boolean',
            'is_featured' => 'boolean',
            'accepts_online_payment' => 'boolean',
            'rating' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function menuCategories()
    {
        return $this->hasMany(MenuCategory::class)->orderBy('sort_order')->orderBy('name');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function staff()
    {
        return $this->hasMany(PartnerStaff::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
