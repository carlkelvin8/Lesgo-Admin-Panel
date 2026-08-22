@props(['label' => '', 'value' => '', 'icon' => '', 'color' => 'blue', 'href' => null, 'format' => ''])

@php
    $tag = $href ? 'a' : 'div';
    $colorMap = [
        'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-500'],
        'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'border' => 'border-green-500'],
        'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600', 'border' => 'border-purple-500'],
        'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-500'],
        'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-500'],
        'yellow' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600', 'border' => 'border-yellow-500'],
    ];
    $c = $colorMap[$color] ?? $colorMap['blue'];
    $classes = "bg-white rounded-xl shadow-sm p-6 border-l-4 {$c['border']}";
    if ($href) $classes .= ' hover:shadow-md transition-shadow';
    $displayValue = $value;
    if ($format === 'currency') $displayValue = number_format((float) $value, 2);
    elseif ($format === 'number') $displayValue = number_format((int) $value);
@endphp

<{{ $tag }} {{ $href ? "href=\"{$href}\"" : '' }} class="{{ $classes }}">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $slot->isNotEmpty() ? $slot : $displayValue }}</p>
        </div>
        @if($icon)
        <div class="w-12 h-12 {{ $c['bg'] }} rounded-full flex items-center justify-center">
            <i class="fas {{ $icon }} {{ $c['text'] }}"></i>
        </div>
        @endif
    </div>
</{{ $tag }}>
