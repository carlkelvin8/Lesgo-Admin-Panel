@extends('admin.layouts.app')
@section('title', 'Services - LesGo Admin')
@section('header', 'Services Management')

@section('actions')
<a href="{{ route('admin.services.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Service</a>
@endsection

@section('content')
<x-filter-panel action="{{ request()->url() }}">
    <x-filter-input name="search" label="Search" placeholder="Search services..." />
    <x-filter-input name="is_active" label="Status" type="select" :options="['' => 'All', '1' => 'Active', '0' => 'Inactive']" />
</x-filter-panel>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Name</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Code</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Partner</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Base Fare</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Per KM</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Min Fare</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Active</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($services as $service)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $service->name }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $service->code }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $service->partner->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">₱{{ number_format($service->base_fare, 2) }}</td>
                        <td class="px-6 py-4">₱{{ number_format($service->per_km_rate, 2) }}</td>
                        <td class="px-6 py-4">₱{{ number_format($service->minimum_fare, 2) }}</td>
                        <td class="px-6 py-4"><x-status-badge :status="$service->is_active ? 'active' : 'inactive'" /></td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.services.show', $service) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-concierge-bell" title="No services found" description="Add a service to start offering rides." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $services->links() }}</div>
</div>
@endsection
