@extends('admin.layouts.app')
@section('title', 'Partners - LesGo Admin')
@section('header', 'Partners Management')

@section('actions')
<a href="{{ route('admin.partners.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Partner</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search partners..." />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All Status', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended']" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Partner</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Category</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Rating</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Open</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Featured</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($partners as $partner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-800">{{ $partner->name }}</p>
                                <p class="text-xs text-gray-500">{{ $partner->user->email ?? 'N/A' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $partner->category ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$partner->status" />
                        </td>
                        <td class="px-6 py-4">{{ $partner->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></td>
                        <td class="px-6 py-4">{{ $partner->is_open ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4">{{ $partner->is_featured ? 'Yes' : 'No' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.partners.show', $partner) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.partners.edit', $partner) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="fa-store" title="No partners found" description="There are no partners to display." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $partners->links() }}</div>
</div>
@endsection
