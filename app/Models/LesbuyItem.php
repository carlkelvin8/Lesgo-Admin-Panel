<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LesbuyItem extends Model
{
    protected $fillable = [
        'order_id', 'menu_item_id', 'name', 'quantity', 'estimated_price', 'actual_price',
        'unit', 'notes', 'image_url', 'is_checklist_item', 'status', 'selected_options',
    ];

    protected function casts(): array
    {
        return [
            'estimated_price' => 'decimal:2',
            'actual_price' => 'decimal:2',
            'is_checklist_item' => 'boolean',
            'selected_options' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
