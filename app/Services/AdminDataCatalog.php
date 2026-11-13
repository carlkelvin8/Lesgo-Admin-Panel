<?php

namespace App\Services;

use App\Models\User;
use App\Traits\SearchEscaping;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminDataCatalog
{
    use SearchEscaping;

    public function availableTo(User $user): array
    {
        return array_filter(config('admin-data', []), fn ($definition) => $user->hasAdminPermission($definition['permission']));
    }

    public function definition(string $key, User $user): array
    {
        $definition = config('admin-data.'.$key);
        abort_unless(is_array($definition), 404);
        abort_unless($user->hasAdminPermission($definition['permission']), 403);
        $definition['key'] = $key;
        $definition['available'] = Schema::hasTable($definition['table']);
        $columns = $definition['available'] ? Schema::getColumnListing($definition['table']) : [];
        foreach (['fields', 'list', 'search', 'masked'] as $name) {
            $definition[$name] = array_values(array_intersect($this->columns($definition[$name] ?? ''), $columns));
        }
        foreach (['status', 'date'] as $name) {
            $definition[$name] = in_array($definition[$name] ?? '', $definition['fields'], true) ? $definition[$name] : null;
        }
        $definition['list'] = array_values(array_intersect($definition['list'], $definition['fields']));
        $definition['search'] = array_values(array_intersect($definition['search'], $definition['fields']));
        $definition['relations'] = array_values(array_intersect(['user_id', 'partner_id', 'driver_id', 'driver_profile_id', 'order_id', 'conversation_id', 'mission_template_id'], $definition['fields']));
        $definition['available'] = $definition['available'] && in_array('id', $definition['fields'], true);

        return $definition;
    }

    public function query(array $definition, Request $request): Builder
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('date_from'), 'after_or_equal:date_from')],
        ]);
        $query = DB::table($definition['table'])->select($definition['fields']);
        foreach ($definition['relations'] as $field) {
            $request->validate([$field => ['nullable', 'integer', 'min:1']]);
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('search')) {
            $pattern = '%'.$this->escapeLikePattern($request->string('search')->toString()).'%';
            $id = filter_var($request->input('search'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $query->where(function (Builder $search) use ($definition, $pattern, $id) {
                $search->whereRaw('1 = 0');
                if ($id !== false) {
                    $search->orWhere('id', $id);
                }
                foreach ($definition['search'] as $column) {
                    $search->orWhereRaw($this->searchLikeSql($column), [$pattern]);
                }
            });
        }
        if ($definition['status'] && $request->filled('status')) {
            $query->where($definition['status'], $request->input('status'));
        }
        if ($definition['date']) {
            if ($request->filled('date_from')) {
                $query->whereDate($definition['date'], '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate($definition['date'], '<=', $request->input('date_to'));
            }
        }

        return $query;
    }

    public function display(array $definition, string $column, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (in_array($column, $definition['masked'], true)) {
            return '••••'.mb_substr((string) $value, -4);
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function columns(string $columns): array
    {
        return array_values(array_filter(explode(',', $columns)));
    }
}
