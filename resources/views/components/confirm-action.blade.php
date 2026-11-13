@props(['action', 'method' => 'POST', 'title' => 'Confirm action', 'message' => 'Are you sure?', 'confirmText' => 'Confirm', 'confirmClass' => 'bg-red-600 hover:bg-red-700'])

<form method="POST" action="{{ $action }}" class="inline" x-data
    @submit.prevent="$dispatch('confirm-modal', {
        title: @js($title),
        message: @js($message),
        confirmText: @js($confirmText),
        confirmClass: @js($confirmClass),
        onConfirm: () => $el.submit()
    })">
    @csrf
    @if(strtoupper($method) !== 'POST') @method($method) @endif
    <button type="submit" {{ $attributes }}>{{ $slot }}</button>
</form>
