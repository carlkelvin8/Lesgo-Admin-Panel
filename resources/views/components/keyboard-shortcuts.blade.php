<div x-data="keyboardShortcuts()" x-init="init()" class="no-print">
    <!-- Help Overlay -->
    <div
        x-show="showHelp"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="showHelp = false"
        class="fixed inset-0 z-[9998] bg-black/50 flex items-center justify-center"
        style="display: none;"
        @click.self="showHelp = false"
    >
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Keyboard Shortcuts</h3>
                <button @click="showHelp = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-700">Open Command Palette</span>
                    <div class="flex gap-1">
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">Ctrl</kbd>
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">K</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-700">Go to Dashboard</span>
                    <div class="flex gap-1">
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">Ctrl</kbd>
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">D</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-700">Go to Users</span>
                    <div class="flex gap-1">
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">Ctrl</kbd>
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">U</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-700">Go to Orders</span>
                    <div class="flex gap-1">
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">Ctrl</kbd>
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">O</kbd>
                    </div>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-gray-700">Show This Help</span>
                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded">?</kbd>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Help Button -->
    <button
        @click="showHelp = true"
        class="no-print fixed bottom-6 right-6 z-[9997] w-10 h-10 bg-purple-600 hover:bg-purple-700 text-white rounded-full shadow-lg flex items-center justify-center text-sm font-bold transition-colors"
        title="Keyboard Shortcuts (?)"
    >
        ?
    </button>

    <script>
        function keyboardShortcuts() {
            return {
                showHelp: false,
                init() {
                    document.addEventListener('keydown', (e) => {
                        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) {
                            return;
                        }
                        if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
                            e.preventDefault();
                            this.showHelp = !this.showHelp;
                            return;
                        }
                        if (e.key === 'Escape') {
                            this.showHelp = false;
                            return;
                        }
                        const ctrl = e.ctrlKey || e.metaKey;
                        if (ctrl && e.key === 'k') {
                            e.preventDefault();
                            document.dispatchEvent(new CustomEvent('open-command-palette'));
                        }
                        if (ctrl && e.key === 'd') {
                            e.preventDefault();
                            window.location.href = '{{ route("admin.dashboard") }}';
                        }
                        if (ctrl && e.key === 'u') {
                            e.preventDefault();
                            window.location.href = '{{ route("admin.users.index") }}';
                        }
                        if (ctrl && e.key === 'o') {
                            e.preventDefault();
                            window.location.href = '{{ route("admin.orders.index") }}';
                        }
                    });
                }
            }
        }
    </script>
</div>