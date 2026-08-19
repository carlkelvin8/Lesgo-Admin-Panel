<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'partner_id', 'menu_category_id', 'name', 'description', 'image_url', 'price',
        'original_price', 'unit', 'is_available', 'is_popular', 'is_featured',
        'is_best_seller', 'requires_prescription', 'sort_order', 'tags', 'options',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_popular' => 'boolean',
            'is_featured' => 'boolean',
            'is_best_seller' => 'boolean',
            'requires_prescription' => 'boolean',
            'tags' => 'array',
            'options' => 'array',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }
}
