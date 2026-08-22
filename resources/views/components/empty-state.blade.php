@props(['icon' => 'fa-inbox', 'title' => 'No data found', 'description' => '', 'actionLabel' => '', 'actionUrl' => '', 'iconBg' => 'bg-gray-100', 'iconColor' => 'text-gray-400'])

<div class="py-16 text-center">
    <div class="empty-state-icon {{ $iconBg }}">
        <i class="fas {{ $icon }} {{ $iconColor }}"></i>
    </div>
    <h3 class="text-lg font-medium text-gray-600 mb-1">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-gray-400 mb-4 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    {{ $slot }}
    @if($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">
            <i class="fas fa-plus text-xs"></i> {{ $actionLabel }}
        </a>
    @endif
</div>
