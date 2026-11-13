@props(['href' => null, 'border' => '', 'hover' => false])

@php
    $tag = $href ? 'a' : 'div';
    $classes = 'bg-white rounded-xl shadow-sm p-6';
    if ($border) $classes .= ' border-l-4 border-' . $border;
    if ($hover && $href) $classes .= ' hover:shadow-md transition-shadow';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->class($classes) }}>
    @isset($header)
        <div class="mb-4">{{ $header }}</div>
    @endisset
    {{ $slot }}
    @isset($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
