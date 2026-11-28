@props(['user'])

@if(blank($user->phone_number))
    <span class="text-gray-500">No number linked</span>
@else
    <span>{{ $user->phone_number }}</span>
    @if($user->phone_verified_at)
        <span class="block text-xs font-medium text-green-700 dark:text-green-300">OTP verified</span>
    @else
        <span class="block text-xs font-medium text-amber-700 dark:text-amber-300">Unverified</span>
    @endif
@endif
