<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait SearchEscaping
{
    protected function escapeLikePattern(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    protected function searchLikeSql(string $column): string
    {
        $connection = DB::connection();
        $operator = $connection->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        return $connection->getQueryGrammar()->wrap($column)." {$operator} ? ESCAPE '!'";
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

        $query->whereRaw($this->searchLikeSql($column), [$pattern]);
    }
}
