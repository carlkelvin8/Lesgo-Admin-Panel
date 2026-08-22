@props(['href' => null, 'border' => '', 'hover' => false])

@php
    $tag = $href ? 'a' : 'div';
    $classes = 'bg-white rounded-xl shadow-sm p-6';
    if ($border) $classes .= ' border-l-4 border-' . $border;
    if ($hover && $href) $classes .= ' hover:shadow-md transition-shadow';
@endphp

<{{ $tag }} {{ $href ? "href=\"{$href}\"" : '' }} class="{{ $classes }}">
    {{ $slot }}
</{{ $tag }}>
