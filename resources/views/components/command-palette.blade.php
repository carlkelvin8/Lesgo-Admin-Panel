<div
    x-data="commandPalette()"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-[9999] flex items-start justify-center pt-[15vh]"
    style="display: none;"
>
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50" @click="open = false"></div>

    <!-- Panel -->
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <!-- Search Input -->
        <div class="flex items-center border-b border-gray-200 px-4">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                x-ref="searchInput"
                x-model="query"
                @keydown.arrow-down.prevent="moveDown()"
                @keydown.arrow-up.prevent="moveUp()"
                @keydown.enter.prevent="selectItem()"
                type="text"
                placeholder="Type a command or search..."
                class="w-full px-3 py-4 text-sm outline-none bg-transparent"
            >
            <kbd class="px-2 py-1 text-xs font-mono text-gray-400 bg-gray-100 border border-gray-300 rounded">Esc</kbd>
        </div>

        <!-- Results -->
        <div class="max-h-80 overflow-y-auto p-2">
            <template x-if="filteredItems.length === 0">
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    No results found
                </div>
            </template>
            <template x-for="(item, index) in filteredItems" :key="item.name">
                <a
                    :href="item.url"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
                    :class="selectedIndex === index ? 'bg-purple-50 text-purple-700' : 'text-gray-700 hover:bg-gray-50'"
                    @mouseenter="selectedIndex = index"
                >
                    <span class="text-lg" x-html="item.icon"></span>
                    <span class="flex-1 font-medium" x-text="item.name"></span>
                    <kbd
                        x-show="item.shortcut"
                        class="px-1.5 py-0.5 text-xs font-mono text-gray-400 bg-gray-100 border border-gray-200 rounded"
                        x-text="item.shortcut"
                    ></kbd>
                </a>
            </template>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 px-4 py-2.5 flex items-center gap-4 text-xs text-gray-400">
            <span class="flex items-center gap-1">
                <kbd class="px-1.5 py-0.5 font-mono bg-gray-100 border border-gray-200 rounded">Enter</kbd>
                select
            </span>
            <span class="flex items-center gap-1">
                <kbd class="px-1.5 py-0.5 font-mono bg-gray-100 border border-gray-200 rounded">&uarr;&darr;</kbd>
                navigate
            </span>
            <span class="flex items-center gap-1">
                <kbd class="px-1.5 py-0.5 font-mono bg-gray-100 border border-gray-200 rounded">Esc</kbd>
                close
            </span>
        </div>
    </div>

    <script>
        function commandPalette() {
            return {
                open: false,
                query: '',
                selectedIndex: 0,
                items: [
                    { name: 'Dashboard', url: '{{ route("admin.dashboard") }}', icon: '&#9632;', shortcut: 'Ctrl+D' },
                    { name: 'Users', url: '{{ route("admin.users.index") }}', icon: '&#9775;', shortcut: 'Ctrl+U' },
                    { name: 'Partners', url: '{{ route("admin.partners.index") }}', icon: '&#9878;' },
                    { name: 'Orders', url: '{{ route("admin.orders.index") }}', icon: '&#9881;', shortcut: 'Ctrl+O' },
                    { name: 'Services', url: '{{ route("admin.services.index") }}', icon: '&#9879;' },
                    { name: 'Payments', url: '{{ route("admin.payments.index") }}', icon: '&#36;' },
                    { name: 'Tickets', url: '{{ route("admin.tickets.index") }}', icon: '&#9993;' },
                    { name: 'Settings', url: '{{ route("admin.security-settings.index") }}', icon: '&#9881;' },
                ],
                get filteredItems() {
                    if (!this.query) return this.items;
                    const q = this.query.toLowerCase();
                    return this.items.filter(item => item.name.toLowerCase().includes(q));
                },
                init() {
                    document.addEventListener('open-command-palette', () => {
                        this.open = true;
                        this.query = '';
                        this.selectedIndex = 0;
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    });
                    this.$watch('query', () => {
                        this.selectedIndex = 0;
                    });
                },
                moveDown() {
                    if (this.selectedIndex < this.filteredItems.length - 1) {
                        this.selectedIndex++;
                    }
                },
                moveUp() {
                    if (this.selectedIndex > 0) {
                        this.selectedIndex--;
                    }
                },
                selectItem() {
                    const item = this.filteredItems[this.selectedIndex];
                    if (item) {
                        window.location.href = item.url;
                    }
                }
            }
        }
    </script>
</div>