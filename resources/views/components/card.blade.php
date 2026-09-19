@props(['href' => null, 'border' => '', 'hover' => false])

@php
    $tag = $href ? 'a' : 'div';
    $classes = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6';
    if ($border) $classes .= ' border-l-4 border-' . $border;
    if ($hover && $href) $classes .= ' hover:shadow-md transition-shadow';
@endphp

<{{ $tag }} {{ $href ? "href=\"{$href}\"" : '' }} class="{{ $classes }}">
    @isset($header)
        <div class="border-b border-gray-100 dark:border-gray-800 pb-4 mb-4">
            {{ $header }}
        </div>
    @endisset
    {{ $slot }}
</{{ $tag }}>
