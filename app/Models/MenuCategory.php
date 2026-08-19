<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $fillable = [
        'partner_id', 'name', 'icon_url', 'icon_emoji', 'description',
        'is_active', 'is_popular', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_popular' => 'boolean'];
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order')->orderBy('name');
    }
}
