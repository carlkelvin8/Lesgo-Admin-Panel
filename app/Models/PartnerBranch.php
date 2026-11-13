<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerBranch extends Model
{
    protected function casts(): array
    {
        return ['opening_hours' => 'array', 'is_open' => 'boolean', 'is_primary' => 'boolean'];
    }
}
