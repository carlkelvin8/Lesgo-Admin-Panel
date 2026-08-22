@props(['status' => '', 'dot' => true, 'label' => ''])

@php
    $colors = [
        'active' => 'bg-green-100 text-green-700',
        'approved' => 'bg-green-100 text-green-700',
        'completed' => 'bg-green-100 text-green-700',
        'paid' => 'bg-green-100 text-green-700',
        'resolved' => 'bg-green-100 text-green-700',
        'delivered' => 'bg-green-100 text-green-700',
        'published' => 'bg-green-100 text-green-700',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'under_review' => 'bg-yellow-100 text-yellow-700',
        'waiting_customer' => 'bg-yellow-100 text-yellow-700',
        'waiting_internal' => 'bg-yellow-100 text-yellow-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'accepted' => 'bg-blue-100 text-blue-700',
        'driver_arrived' => 'bg-blue-100 text-blue-700',
        'picked_up' => 'bg-indigo-100 text-indigo-700',
        'open' => 'bg-blue-100 text-blue-700',
        'inactive' => 'bg-gray-100 text-gray-600',
        'draft' => 'bg-gray-100 text-gray-600',
        'closed' => 'bg-gray-100 text-gray-600',
        'cancelled' => 'bg-red-100 text-red-700',
        'rejected' => 'bg-red-100 text-red-700',
        'failed' => 'bg-red-100 text-red-700',
        'suspended' => 'bg-red-100 text-red-700',
        'flagged' => 'bg-orange-100 text-orange-700',
        'refunded' => 'bg-purple-100 text-purple-700',
        'partial' => 'bg-orange-100 text-orange-700',
        'needs_review' => 'bg-orange-100 text-orange-700',
        'high' => 'bg-red-100 text-red-700',
        'urgent' => 'bg-red-200 text-red-800',
        'medium' => 'bg-yellow-100 text-yellow-700',
        'low' => 'bg-gray-100 text-gray-600',
        'critical' => 'bg-red-200 text-red-800',
        // Roles
        'admin' => 'bg-purple-100 text-purple-700',
        'super_admin' => 'bg-red-100 text-red-700',
        'driver' => 'bg-blue-100 text-blue-700',
        'partner' => 'bg-green-100 text-green-700',
        'customer' => 'bg-gray-100 text-gray-600',
        'operations' => 'bg-orange-100 text-orange-700',
        // Revenue types
        'ride' => 'bg-blue-100 text-blue-700',
        'delivery' => 'bg-green-100 text-green-700',
        'food' => 'bg-orange-100 text-orange-700',
        'subscription' => 'bg-purple-100 text-purple-700',
        'commission' => 'bg-yellow-100 text-yellow-700',
        'lesbuy' => 'bg-indigo-100 text-indigo-700',
        // Top-up / payment statuses
        'approved' => 'bg-green-100 text-green-700',
        'processing' => 'bg-blue-100 text-blue-700',
        'confirmed' => 'bg-green-100 text-green-700',
        'verified' => 'bg-green-100 text-green-700',
        'unverified' => 'bg-yellow-100 text-yellow-700',
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-700';
    $displayLabel = $label ?: str_replace('_', ' ', ucfirst($status));
@endphp

<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ str_contains($color, 'green') ? 'bg-green-500' : (str_contains($color, 'red') ? 'bg-red-500' : (str_contains($color, 'yellow') ? 'bg-yellow-500' : (str_contains($color, 'blue') ? 'bg-blue-500' : (str_contains($color, 'purple') ? 'bg-purple-500' : (str_contains($color, 'orange') ? 'bg-orange-500' : (str_contains($color, 'indigo') ? 'bg-indigo-500' : 'bg-gray-500')))))) }}"></span>
    @endif
    {{ $displayLabel }}
</span>
