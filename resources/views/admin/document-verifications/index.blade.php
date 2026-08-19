@extends('admin.layouts.app')
@section('title', 'Document Verifications - LesGo Admin')
@section('header', 'Document Verifications')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="User name or email..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Document Type</label>
            <select name="document_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <option value="">All Types</option>
                @foreach(['drivers_license','national_id','passport','vehicle_registration','other'] as $type)
                    <option value="{{ $type }}" {{ request('document_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                <option value="">All Status</option>
                @foreach(['pending','under_review','approved','rejected','expired'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
        <a href="{{ route('admin.document-verifications.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'under_review' => 'bg-blue-100 text-blue-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'expired' => 'bg-gray-100 text-gray-800',
                    ];
                @endphp
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
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$v->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $v->status)) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $v->submitted_at?->diffForHumans() ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $v->verifier->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.document-verifications.show', $v) }}" class="text-blue-600 hover:text-blue-800" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No document verifications found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">
        {{ $verifications->links() }}
    </div>
</div>
@endsection
