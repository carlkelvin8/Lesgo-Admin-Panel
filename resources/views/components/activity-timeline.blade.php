@props(['entries'])

<div class="space-y-4">
    @forelse ($entries as $entry)
        <div class="relative pl-8 transition-colors duration-150 hover:bg-gray-50 rounded-lg p-3 group">
            {{-- Timeline line --}}
            <div class="absolute left-3 top-0 bottom-0 w-px bg-gray-200 group-last:hidden"></div>

            {{-- Colored dot --}}
            <div class="absolute left-1.5 top-4 w-3 h-3 rounded-full border-2 border-white shadow
                {{ match($entry->risk_level ?? 'safe') {
                    'high' => 'bg-red-500',
                    'low' => 'bg-yellow-400',
                    default => 'bg-green-500',
                } }}">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                        {{ $entry->action }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $entry->resource_type }} #{{ $entry->resource_id }}
                        <span class="mx-1">·</span>
                        by {{ $entry->user_name ?? 'System' }}
                    </p>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap">
                    {{ $entry->created_at->diffForHumans() }}
                </span>
            </div>

            @if ($entry->old_values || $entry->new_values)
                <div x-data="{ open: false }" class="mt-2">
                    <button
                        @click="open = !open"
                        class="text-xs text-blue-600 hover:text-blue-800 focus:outline-none flex items-center gap-1"
                    >
                        <svg
                            class="w-3 h-3 transition-transform duration-150"
                            :class="{ 'rotate-90': open }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        View changes
                    </button>
                    <div x-show="open" x-collapse class="mt-2 text-xs bg-gray-50 rounded p-2 font-mono space-y-1">
                        @if ($entry->old_values)
                            <div>
                                <span class="text-red-600 font-semibold">Old:</span>
                                <pre class="text-gray-700 whitespace-pre-wrap">{{ json_encode($entry->old_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                        @if ($entry->new_values)
                            <div>
                                <span class="text-green-600 font-semibold">New:</span>
                                <pre class="text-gray-700 whitespace-pre-wrap">{{ json_encode($entry->new_values, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 text-center py-4">No activity recorded yet.</p>
    @endforelse
</div>
