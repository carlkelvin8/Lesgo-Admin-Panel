@extends('admin.layouts.app')
@section('title', 'Document Verifications - LesGo Admin')
@section('header', 'Document Verifications')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="User name or email..." value="{{ request('search') }}" />
        <x-filter-input name="document_type" label="Document Type" type="select" :options="['' => 'All Types', 'drivers_license' => 'Drivers License', 'national_id' => 'National Id', 'passport' => 'Passport', 'vehicle_registration' => 'Vehicle Registration', 'other' => 'Other']" :selected="request('document_type')" />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All Status', 'pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'expired' => 'Expired']" :selected="request('status')" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Document Type</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Document #</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Submitted</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Reviewed By</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($verifications as $v)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                                    {{ $v->user ? substr($v->user->name, 0, 1) : '?' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $v->user->name ?? 'Deleted User' }}</p>
                                    <p class="text-xs text-gray-500">{{ $v->user->email ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ ucfirst(str_replace('_', ' ', $v->document_type)) }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $v->document_number ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$v->status" />
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $v->submitted_at?->diffForHumans() ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $v->verifier->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.document-verifications.show', $v) }}" class="text-blue-600 hover:text-blue-800" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="fa-id-card" title="No document verifications found" description="Document verification requests will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">
        {{ $verifications->links() }}
    </div>
</div>
@endsection
