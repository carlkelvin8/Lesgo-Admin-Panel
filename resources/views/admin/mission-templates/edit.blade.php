@extends('admin.layouts.app')
@section('title', 'Edit Mission - LesGo Admin')
@section('header', 'Edit Mission: ' . $missionTemplate->title)

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.mission-templates.update', $missionTemplate) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title', $missionTemplate->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $missionTemplate->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience *</label>
                    <select name="target_audience" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="customer" @selected(old('target_audience', $missionTemplate->target_audience)=='customer')>Customer</option>
                        <option value="driver" @selected(old('target_audience', $missionTemplate->target_audience)=='driver')>Driver</option>
                        <option value="merchant" @selected(old('target_audience', $missionTemplate->target_audience)=='merchant')>Merchant</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="daily" @selected(old('type', $missionTemplate->type)=='daily')>Daily</option>
                        <option value="weekly" @selected(old('type', $missionTemplate->type)=='weekly')>Weekly</option>
                        <option value="monthly" @selected(old('type', $missionTemplate->type)=='monthly')>Monthly</option>
                        <option value="one_time" @selected(old('type', $missionTemplate->type)=='one_time')>One Time</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Goal Type *</label>
                    <select name="goal_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <optgroup label="Generic">
                            <option value="complete_orders" @selected(old('goal_type', $missionTemplate->goal_type)=='complete_orders')>Complete Orders</option>
                            <option value="specific_service" @selected(old('goal_type', $missionTemplate->goal_type)=='specific_service')>Specific Service</option>
                            <option value="get_rating" @selected(old('goal_type', $missionTemplate->goal_type)=='get_rating')>Get Rating</option>
                            <option value="refer_friend" @selected(old('goal_type', $missionTemplate->goal_type)=='refer_friend')>Refer Friend</option>
                        </optgroup>
                        <optgroup label="Customer Specific">
                            <option value="leseat_order" @selected(old('goal_type', $missionTemplate->goal_type)=='leseat_order')>LesEat Order</option>
                            <option value="lesride_orders" @selected(old('goal_type', $missionTemplate->goal_type)=='lesride_orders')>LesRide Orders</option>
                            <option value="friend_referral" @selected(old('goal_type', $missionTemplate->goal_type)=='friend_referral')>Friend Referral</option>
                            <option value="app_review" @selected(old('goal_type', $missionTemplate->goal_type)=='app_review')>App Review</option>
                            <option value="social_follow" @selected(old('goal_type', $missionTemplate->goal_type)=='social_follow')>Social Follow</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Goal Target *</label>
                    <input type="number" name="goal_target" value="{{ old('goal_target', $missionTemplate->goal_target) }}" required min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reward Amount (₱) *</label>
                    <input type="number" step="0.01" name="reward_amount" value="{{ old('reward_amount', $missionTemplate->reward_amount) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reward Currency</label>
                    <input type="text" name="reward_currency" value="{{ old('reward_currency', $missionTemplate->reward_currency) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Service Code</label>
                <input type="text" name="service_code" value="{{ old('service_code', $missionTemplate->service_code) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $missionTemplate->is_active)) class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.mission-templates.show', $missionTemplate) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
