@props(['title' => 'Confirm Action', 'message' => 'Are you sure you want to proceed?', 'confirmText' => 'Confirm', 'cancelText' => 'Cancel', 'confirmClass' => 'bg-red-600 hover:bg-red-700', 'iconClass' => 'fas fa-exclamation-triangle text-red-600', 'iconBg' => 'bg-red-100', 'id' => null])

<div x-data="{ open: false }" x-on:open-confirm.window="$event.detail.id === '{{ $id }}' && (open = true, window._confirmCallback_{{ $id }} = $event.detail.onConfirm)">
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $iconBg }}"><i class="{{ $iconClass }}"></i></div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $title }}</h3>
            </div>
            <p class="text-gray-600 text-sm mb-6">{{ $message }}</p>
            {{ $slot }}
            <div class="flex justify-end gap-3">
                <button @click="open = false" class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 transition">{{ $cancelText }}</button>
                <button @click="if(window._confirmCallback_{{ $id }}) window._confirmCallback_{{ $id }}(); open = false;" class="px-4 py-2 text-sm rounded-lg text-white transition {{ $confirmClass }}">{{ $confirmText }}</button>
            </div>
        </div>
    </div>
</div>
