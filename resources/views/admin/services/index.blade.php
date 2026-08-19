@extends('admin.layouts.app')
@section('title', 'Services - LesGo Admin')
@section('header', 'Services Management')

@section('actions')
<a href="{{ route('admin.services.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Service</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search services..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <select name="is_active" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                        <td class="px-6 py-4">
                            @if($service->is_active)
                                <span class="text-green-600 text-xs"><i class="fas fa-circle text-green-500 mr-1" style="font-size:6px"></i>Active</span>
                            @else
                                <span class="text-red-600 text-xs"><i class="fas fa-circle text-red-500 mr-1" style="font-size:6px"></i>Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.services.show', $service) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No services found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $services->links() }}</div>
</div>
@endsection
