@extends('admin.layouts.app')
@section('title', 'Review Detail - LesGo Admin')
@section('header', 'Review Detail')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Review Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-lg">
                        {{ $review->is_anonymous ? '?' : substr($review->user->name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $review->is_anonymous ? 'Anonymous' : ($review->user->name ?? '-') }}</h3>
                        <p class="text-xs text-gray-500">{{ $review->is_anonymous ? '' : ($review->user->email ?? '') }}</p>
                    </div>
                </div>
                <x-status-badge :status="$review->status" />
            </div>

            @if($review->order)
                <div class="mb-4 text-sm">
                    <span class="text-gray-500">Order:</span>
                    <a href="{{ route('admin.orders.show', $review->order) }}" class="text-blue-600 hover:underline ml-1">#{{ $review->order->id }}</a>
                </div>
            @endif

            <!-- Overall Rating -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Overall Rating</h4>
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= $review->overall_rating)
                            <i class="fas fa-star text-yellow-400 text-lg"></i>
                        @else
                            <i class="far fa-star text-gray-300 text-lg"></i>
                        @endif
                    @endfor
                    <span class="ml-2 text-gray-600 text-sm">{{ $review->overall_rating }}/5</span>
                </div>
            </div>

            <!-- Detailed Ratings -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                @foreach([
                    ['label' => 'Service', 'value' => $review->service_rating],
                    ['label' => 'Driver', 'value' => $review->driver_rating],
                    ['label' => 'Delivery Time', 'value' => $review->delivery_time_rating],
                    ['label' => 'Communication', 'value' => $review->communication_rating],
                    ['label' => 'Professionalism', 'value' => $review->professionalism_rating],
                ] as $rating)
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-xs text-gray-500 mb-1">{{ $rating['label'] }}</p>
                        <div class="flex items-center justify-center gap-0.5">
                            @if($rating['value'])
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $rating['value'])
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                    @else
                                        <i class="far fa-star text-gray-300 text-xs"></i>
                                    @endif
                                @endfor
                                <span class="ml-1 text-xs text-gray-500">{{ $rating['value'] }}</span>
                            @else
                                <span class="text-xs text-gray-400">N/A</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Review Content -->
            @if($review->review_title)
                <h4 class="font-semibold text-gray-800 mb-2">{{ $review->review_title }}</h4>
            @endif

            @if($review->review_comment)
                <p class="text-gray-600 text-sm leading-relaxed mb-4">{{ $review->review_comment }}</p>
            @endif

            <!-- Review Tags -->
            @if($review->review_tags && count($review->review_tags) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Tags</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($review->review_tags as $tag)
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs">{{ $tag }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Review Images -->
            @if($review->review_images && count($review->review_images) > 0)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Images</h4>
                    <div class="flex flex-wrap gap-3">
                        @foreach($review->review_images as $image)
                            <img src="{{ $image }}" alt="Review image" class="w-20 h-20 object-cover rounded-lg border">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Business Response -->
        @if($review->business_response)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h4 class="font-semibold text-gray-800 mb-2"><i class="fas fa-reply mr-2 text-blue-600"></i>Business Response</h4>
                <p class="text-gray-600 text-sm">{{ $review->business_response }}</p>
                @if($review->business_responded_at)
                    <p class="text-xs text-gray-400 mt-2">{{ $review->business_responded_at->diffForHumans() }}</p>
                @endif
            </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Moderation Form -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h4 class="font-semibold text-gray-800 mb-4"><i class="fas fa-shield-alt mr-2 text-blue-600"></i>Moderation</h4>
            <form method="POST" action="{{ route('admin.ratings.update', $review) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                        @foreach(['pending', 'approved', 'rejected', 'flagged'] as $status)
                            <option value="{{ $status }}" {{ $review->status === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Moderation Notes</label>
                    <textarea name="moderation_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none text-sm" placeholder="Internal notes...">{{ old('moderation_notes', $review->moderation_notes) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Response</label>
                    <textarea name="business_response" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none text-sm" placeholder="Public response to the customer...">{{ old('business_response', $review->business_response) }}</textarea>
                </div>

                <div class="space-y-2 mb-4">
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $review->is_featured) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Featured</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="hidden" name="is_public" value="0">
                        <input type="checkbox" name="is_public" value="1" {{ old('is_public', $review->is_public) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">Public</span>
                    </label>
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium w-full">Update Review</button>
            </form>
        </div>

        <!-- Metadata -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h4 class="font-semibold text-gray-800 mb-4"><i class="fas fa-info-circle mr-2 text-blue-600"></i>Metadata</h4>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Verified</span>
                    <span class="text-{{ $review->is_verified ? 'green' : 'gray' }}-600">{{ $review->is_verified ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Featured</span>
                    <span class="text-{{ $review->is_featured ? 'green' : 'gray' }}-600">{{ $review->is_featured ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Public</span>
                    <span class="text-{{ $review->is_public ? 'green' : 'gray' }}-600">{{ $review->is_public ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Anonymous</span>
                    <span class="text-{{ $review->is_anonymous ? 'green' : 'gray' }}-600">{{ $review->is_anonymous ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Created</span>
                    <span class="text-gray-800">{{ $review->created_at->format('M d, Y H:i') }}</span>
                </div>
                @if($review->moderated_at)
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-500">Moderated</span>
                        <span class="text-gray-800">{{ $review->moderated_at->format('M d, Y H:i') }}</span>
                    </div>
                @endif
                @if($review->moderator)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Moderated By</span>
                        <span class="text-gray-800">{{ $review->moderator->name }}</span>
                    </div>
                @endif
            </div>
        </div>

        <a href="{{ route('admin.ratings.index') }}" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to List</a>
    </div>
</div>
@endsection
