@props(['type' => 'text', 'count' => 1, 'class' => ''])

@for($i = 0; $i < $count; $i++)
    @if($type === 'text')
        <div class="skeleton skeleton-text {{ $class }}"></div>
    @elseif($type === 'title')
        <div class="skeleton skeleton-title {{ $class }}"></div>
    @elseif($type === 'avatar')
        <div class="skeleton skeleton-avatar w-10 h-10 {{ $class }}"></div>
    @elseif($type === 'card')
        <div class="skeleton skeleton-card {{ $class }}"></div>
    @elseif($type === 'row')
        <div class="flex items-center gap-3 p-3 {{ $class }}">
            <x-skeleton type="avatar" />
            <div class="flex-1 space-y-2">
                <x-skeleton type="text" class="w-1/3" />
                <x-skeleton type="text" class="w-2/3" />
            </div>
        </div>
    @endif
@endfor
