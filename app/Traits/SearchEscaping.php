<?php

namespace App\Traits;

trait SearchEscaping
{
    protected function escapeLikePattern(string $value): string
    {
        return addcslashes($value, '%_');
    }

    protected function scopeSearchLike($query, string $column, ?string $search, string $type = 'both'): void
    {
        if ($search === null || trim($search) === '') {
            return;
        }

        $escaped = $this->escapeLikePattern(trim($search));
        $pattern = match ($type) {
            'start' => "{$escaped}%",
            'end' => "%{$escaped}",
            default => "%{$escaped}%",
        };

        $query->where($column, 'like', $pattern);
    }
}
