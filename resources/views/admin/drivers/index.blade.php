@extends('admin.layouts.app')
@section('title', 'Drivers - LesGo Admin')
@section('header', 'Drivers Management')

@section('actions')
<a href="{{ route('admin.drivers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Driver</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, license..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                @foreach(['pending','active','inactive','suspended'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
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
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Driver</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">License</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Vehicle</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Rating</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Trips</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Package</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($drivers as $driver)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-sm">{{ substr($driver->user->name ?? '?', 0, 1) }}</div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $driver->user->name ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $driver->user->email ?? '' }}</p>
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
                            @php $sc = ['pending'=>'bg-yellow-100 text-yellow-800','active'=>'bg-green-100 text-green-800','inactive'=>'bg-gray-100 text-gray-800','suspended'=>'bg-red-100 text-red-800']; @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $sc[$driver->status] ?? 'bg-gray-100' }}">{{ ucfirst($driver->status) }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $driver->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></td>
                        <td class="px-6 py-4">{{ $driver->total_trips }}</td>
                        <td class="px-6 py-4 text-xs">{{ $driver->package_tier ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.drivers.edit', $driver) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No drivers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $drivers->links() }}</div>
</div>
@endsection
