@extends('admin.layouts.app')
@section('title', 'Missions - LesGo Admin')
@section('header', 'Missions Management')

@section('actions')
<a href="{{ route('admin.mission-templates.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Mission</a>
@endsection

@section('content')
<x-filter-panel action="{{ request()->url() }}">
    <x-filter-input name="search" label="Search" placeholder="Search missions..." />
    <x-filter-input name="target_audience" label="Audience" type="select" :options="['' => 'All', 'customer' => 'Customer', 'driver' => 'Driver', 'merchant' => 'Merchant']" />
    <x-filter-input name="type" label="Type" type="select" :options="['' => 'All', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'one_time' => 'One Time']" />
    <x-filter-input name="is_active" label="Status" type="select" :options="['' => 'All', '1' => 'Active', '0' => 'Inactive']" />
</x-filter-panel>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Title</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Audience</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Goal</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Reward</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Active</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($templates as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $t->title }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $t->description }}</div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full
                            @if($t->target_audience==='customer') bg-blue-100 text-blue-700
                            @elseif($t->target_audience==='driver') bg-green-100 text-green-700
                            @else bg-orange-100 text-orange-700 @endif
                        ">{{ $t->target_audience }}</span></td>
                        <td class="px-6 py-4 text-xs">
                            <div class="font-mono">{{ $t->goal_type }} x{{ $t->goal_target }}</div>
                            @if($t->service_code)<div class="text-gray-500">service: {{ $t->service_code }}</div>@endif
                        </td>
                        <td class="px-6 py-4">₱{{ number_format($t->reward_amount,2) }} <span class="text-xs text-gray-500">{{ $t->reward_currency }}</span></td>
                        <td class="px-6 py-4 text-xs">{{ $t->type }}</td>
                        <td class="px-6 py-4"><x-status-badge :status="$t->is_active ? 'active' : 'inactive'" /></td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.mission-templates.show', $t) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.mission-templates.edit', $t) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="fa-bullseye" title="No missions found" description="Create a mission to engage users." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $templates->links() }}</div>
</div>
@endsection
