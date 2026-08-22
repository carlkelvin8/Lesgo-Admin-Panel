@extends('admin.layouts.app')
@section('title', 'Ratings & Reviews - LesGo Admin')
@section('header', 'Ratings & Reviews')

@section('content')
<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search User" placeholder="User name..." value="{{ request('search') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'flagged' => 'Flagged']" :selected="request('status')" />
        <x-filter-input name="min_rating" label="Min Rating" type="select" :options="['' => 'Any', 1 => '1+', 2 => '2+', 3 => '3+', 4 => '4+', 5 => '5+']" :selected="request('min_rating')" />
        <x-filter-input name="max_rating" label="Max Rating" type="select" :options="['' => 'Any', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']" :selected="request('max_rating')" />
    </x-filter-panel>
</div>

<!-- Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Order</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Rating</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Review</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reviews as $review)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-sm">
                                    {{ $review->is_anonymous ? '?' : substr($review->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $review->is_anonymous ? 'Anonymous' : ($review->user->name ?? '-') }}</p>
                                    <p class="text-xs text-gray-500">{{ $review->is_anonymous ? '' : ($review->user->email ?? '') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($review->order)
                                <a href="{{ route('admin.orders.show', $review->order) }}" class="text-blue-600 hover:underline">#{{ $review->order->id }}</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $review->overall_rating)
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                    @else
                                        <i class="far fa-star text-gray-300 text-xs"></i>
                                    @endif
                                @endfor
                                <span class="ml-1 text-gray-500 text-xs">{{ $review->overall_rating }}/5</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-gray-600 truncate max-w-[200px]">{{ $review->review_title ?? Str::limit($review->review_comment, 50) ?? '-' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$review->status" />
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $review->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.ratings.show', $review) }}" class="text-blue-600 hover:text-blue-800" title="View"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="fa-star" title="No ratings or reviews found" description="Ratings and reviews will appear here once customers submit them." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">
        {{ $reviews->links() }}
    </div>
</div>
@endsection
