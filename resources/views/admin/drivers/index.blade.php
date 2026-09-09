@extends('admin.layouts.app')
@section('title', 'Drivers - LesGo Admin')
@section('header', 'Drivers Management')

@section('actions')
<a href="{{ route('admin.drivers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Driver</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-center">
        <div class="rounded-lg bg-green-50 p-3"><div class="text-xs text-gray-500">Paid via PayMongo</div><div class="text-xl font-bold text-green-700">{{ $feeStats['paid'] }}</div></div>
        <div class="rounded-lg bg-purple-50 p-3"><div class="text-xs text-gray-500">Admin Waived</div><div class="text-xl font-bold text-purple-700">{{ $feeStats['waived'] }}</div></div>
        <div class="rounded-lg bg-yellow-50 p-3"><div class="text-xs text-gray-500">Not Paid</div><div class="text-xl font-bold text-yellow-700">{{ $feeStats['unpaid'] }}</div></div>
        <div class="rounded-lg bg-gray-50 p-3"><div class="text-xs text-gray-500">No Fee Record</div><div class="text-xl font-bold text-gray-700">{{ $feeStats['no_record'] }}</div></div>
    </div>
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search by name, license..." value="{{ request('search') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['pending' => 'Pending', 'active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" />
        <x-filter-input name="fee_status" label="Registration Fee" type="select" :options="['' => 'All', 'paid' => 'Paid via PayMongo', 'waived' => 'Waived by Admin', 'unpaid' => 'Unpaid', 'pending' => 'Payment Pending', 'failed' => 'Payment Failed', 'expired' => 'Payment Expired', 'no_record' => 'No Fee Record']" />
    </x-filter-panel>
</div>

<form method="POST" action="{{ route('admin.drivers.bulk-destroy') }}"
    x-data="{ selected: [] }"
    x-on:submit="if (!confirm(`Permanently delete ${selected.length} selected rider(s) and ALL linked orders, payments, wallets, documents, messages, and accounts?`)) $event.preventDefault()">
    @csrf @method('DELETE')
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-3 border-b bg-gray-50 flex items-center justify-between gap-4">
        <span class="text-sm text-gray-600"><span x-text="selected.length"></span> selected</span>
        <button type="submit" :disabled="selected.length === 0" class="bg-red-600 hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-trash mr-1"></i> Delete Selected</button>
    </div>
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 w-10"><input type="checkbox" aria-label="Select all riders on this page" @change="selected = $event.target.checked ? @js($drivers->pluck('id')->map(fn ($id) => (string) $id)->values()) : []"></th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Driver</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">License</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Vehicle</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Registration Fee</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Rating</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Trips</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Package</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($drivers as $driver)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4"><input type="checkbox" name="ids[]" value="{{ $driver->id }}" x-model="selected" aria-label="Select {{ $driver->user?->name ?? 'rider' }}"></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-sm">{{ substr($driver->user?->name ?? '?', 0, 1) }}</div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $driver->user?->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $driver->user?->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-xs">{{ $driver->license_number ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            {{ $driver->vehicle_type ?? '-' }}
                            @if($driver->plate_number)
                                <span class="block text-gray-400">{{ $driver->plate_number }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge status="{{ $driver->status }}" />
                        </td>
                        <td class="px-6 py-4">
                            @php($fee = $driver->registrationFee)
                            @if(!$fee)
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">No Record</span>
                            @elseif($fee->isPaid())
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Paid</span>
                                <span class="block mt-1 text-xs text-gray-400">PayMongo · {{ $fee->payment_date?->format('M d, Y') ?? 'verified' }}</span>
                            @elseif($fee->isWaived())
                                <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">Waived</span>
                                <span class="block mt-1 text-xs text-gray-400">Admin · {{ $fee->waived_at?->format('M d, Y') ?? 'recorded' }}</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full {{ $fee->payment_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($fee->payment_status) }}</span>
                                <span class="block mt-1 text-xs text-gray-400">Not paid</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $driver->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></td>
                        <td class="px-6 py-4">{{ $driver->total_trips }}</td>
                        <td class="px-6 py-4 text-xs">{{ $driver->package_tier ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.drivers.edit', $driver) }}" class="text-yellow-600 hover:text-yellow-800 mr-2"><i class="fas fa-edit"></i></a>
                            <button type="button" class="text-red-600 hover:text-red-800" title="Delete rider profile"
                                x-data
                                @click="$dispatch('confirm-modal', {
                                    title: 'Delete Rider Profile',
                                    message: 'Permanently delete {{ addslashes($driver->user?->name ?? 'this rider') }}, the linked user account, orders, payments, wallet activity, documents, messages, reviews, and all other linked data? This cannot be undone.',
                                    confirmText: 'Delete Rider & Data',
                                    onConfirm: () => {
                                        const form = document.createElement('form'); form.method = 'POST'; form.action = '{{ route('admin.drivers.destroy', $driver) }}';
                                        const token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}'; form.appendChild(token);
                                        const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE'; form.appendChild(method);
                                        document.body.appendChild(form); form.submit();
                                    }
                                })"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10"><x-empty-state icon="fa-motorcycle" title="No drivers found" description="There are no drivers matching your criteria." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $drivers->links() }}</div>
</div>
</form>
@endsection
