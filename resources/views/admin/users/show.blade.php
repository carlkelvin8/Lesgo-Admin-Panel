@extends('admin.layouts.app')
@section('title', 'User Details - LesGo Admin')
@section('header', 'User Details')

@section('actions')
@if(auth()->user()->hasAdminPermission('users.manage') && ! $user->trashed())
<a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
@unless($user->is(auth()->user()) || $user->trashed())
<x-confirm-action :action="route('admin.users.toggle', $user)"
    :title="($user->is_active ? 'Deactivate' : 'Activate').' User'"
    :message="($user->is_active ? 'Deactivate ' : 'Activate ').$user->name.'?'"
    :confirm-text="$user->is_active ? 'Deactivate' : 'Activate'"
    class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white">
    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
</x-confirm-action>
<x-confirm-action :action="route('admin.users.destroy', $user)" method="DELETE"
    title="Delete User" :message="'Delete '.$user->name.'? They will lose account access.'"
    confirm-text="Delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">
    Delete
</x-confirm-action>
@endunless
@endif
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center">
            @if($selfieUrl)
                <img src="{{ $selfieUrl }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover mx-auto mb-4 border">
            @else
                <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                    {{ substr($user->name, 0, 1) }}
                </div>
            @endif
            <h3 class="text-xl font-bold text-gray-800">{{ $user->name }}</h3>
            <p class="text-gray-500">{{ $user->email }}</p>
            <x-status-badge :status="$user->role" />
            @if($user->trashed())
                <p class="mt-2 text-sm text-gray-500">Deleted account</p>
            @endif
        </div>
        <div class="mt-6 space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Phone</span>
                <span class="text-gray-800"><x-user-phone :user="$user" /></span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Role</span>
                <span class="text-gray-800">{{ ucfirst($user->role) }} @if($user->isAdmin()) ({{ $user->adminRoleLabel() }}) @endif</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Status</span>
                <span class="text-{{ $user->is_active ? 'green' : 'red' }}-600 font-medium">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Joined</span>
                <span class="text-gray-800">{{ $user->created_at->format('M d, Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Last Updated</span>
                <span class="text-gray-800">{{ $user->updated_at->diffForHumans() }}</span>
            </div>
        </div>

        @if($user->isAdmin())
        @php
            $roleDef = \App\Models\AdminRole::definition($user->effectiveAdminRole());
            $rolePerms = $roleDef?->permissions ?? [];
            $extraPerms = $user->admin_permissions ?? [];
            $effective = $user->isSuperAdmin() ? ['*'] : array_unique(array_merge($rolePerms, $extraPerms));
        @endphp
        <div class="mt-6 pt-4 border-t">
            <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Effective Permissions</h4>
            @if($user->isSuperAdmin())
                <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700"><i class="fas fa-crown text-[10px]"></i> Full Access (*)</span>
            @else
                <div class="flex flex-wrap gap-1.5">
                    @forelse($effective as $perm)
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ in_array($perm, $extraPerms) ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-700' }}">{{ $perm }} @if(in_array($perm, $extraPerms)) <span class="text-[10px]">+extra</span> @endif</span>
                    @empty
                        <span class="text-xs text-gray-400">No permissions</span>
                    @endforelse
                </div>
                @if(!empty($extraPerms))
                <p class="mt-2 text-xs text-gray-500">{{ count($extraPerms) }} extra permission(s) on top of {{ $roleDef?->label ?? $user->effectiveAdminRole() }}</p>
                @endif
            @endif
        </div>
        @endif
    </div>

    <!-- Activity -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Driver Profile -->
        @if($user->driverProfile)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-motorcycle mr-2 text-green-600"></i>Driver Profile</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><p class="text-gray-500">Status</p><p class="font-medium">{{ ucfirst($user->driverProfile->status) }}</p></div>
                <div><p class="text-gray-500">Rating</p><p class="font-medium">{{ $user->driverProfile->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></p></div>
                <div><p class="text-gray-500">Total Trips</p><p class="font-medium">{{ $user->driverProfile->total_trips }}</p></div>
                <div><p class="text-gray-500">License</p><p class="font-medium">{{ $user->driverProfile->license_number ?? '-' }}</p></div>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasAdminPermission('verifications.manage'))
        <!-- Identity Documents -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-id-card mr-2 text-purple-600"></i>Identity Documents</h3>
            </div>
            <div class="p-6 space-y-6">
                @forelse($identityVerifications as $verification)
                    <section class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-800">Government ID</p>
                                <p class="text-xs text-gray-500">Submitted {{ ($verification->submitted_at ?? $verification->created_at)?->format('M d, Y') ?? 'date unavailable' }}</p>
                            </div>
                            <x-status-badge :status="$verification->status" />
                        </div>

                        @php
                            $documentUrls = collect($verification->document_urls ?? [])
                                ->filter(function ($url) {
                                    return is_string($url) && trim($url) !== '';
                                })
                                ->map(fn ($path) => \App\Services\MediaUrlService::publicUrl($path))
                                ->filter();
                        @endphp
                        @if($documentUrls->isNotEmpty())
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($documentUrls as $index => $documentUrl)
                                    @php
                                        $extension = strtolower(pathinfo(parse_url($documentUrl ?? '', PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'], true);
                                    @endphp
                                    @if($documentUrl)
                                        <a href="{{ $documentUrl }}" target="_blank" rel="noopener noreferrer" class="group block rounded-lg border border-gray-200 p-3 hover:border-blue-400 hover:shadow-sm">
                                            @if($isImage)
                                                <img src="{{ $documentUrl }}" alt="Government ID image {{ $index + 1 }}" class="w-full h-48 object-contain bg-gray-50 rounded">
                                            @else
                                                <div class="h-48 flex flex-col items-center justify-center gap-3 rounded bg-gray-50 text-gray-500">
                                                    <i class="fas {{ $extension === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file-alt' }} text-4xl"></i>
                                                    <span class="text-sm">{{ $extension === 'pdf' ? 'PDF document' : 'Document attachment' }}</span>
                                                </div>
                                            @endif
                                            <span class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-blue-600 group-hover:text-blue-800">
                                                <i class="fas fa-external-link-alt text-xs"></i>
                                                Open ID {{ $index === 0 ? 'Front' : ($index === 1 ? 'Back' : 'Attachment '.($index + 1)) }}
                                            </span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No ID files were attached to this verification.</p>
                        @endif
                    </section>
                @empty
                    <p class="text-sm text-gray-500">No identity documents uploaded.</p>
                @endforelse
            </div>
        </div>

        @endif

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-shopping-cart mr-2 text-blue-600"></i>Recent Orders</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th>
                            <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($user->orders as $order)
                            <tr>
                                <td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">#{{ $order->id }}</a></td>
                                <td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100">{{ ucfirst($order->status) }}</span></td>
                                <td class="px-6 py-3">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td>
                                <td class="px-6 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
