@props(['action' => ''])

<form method="GET" action="{{ $action }}" class="bg-white rounded-xl shadow-sm p-4 mb-6" x-data="{ loading: false }" x-on:submit="loading = true">
    <div class="flex flex-wrap gap-3 items-end">
        {{ $slot }}
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 flex items-center gap-2 transition">
            <i class="fas fa-filter"></i> Filter
            <span x-show="loading" x-cloak><i class="fas fa-spinner fa-spin"></i></span>
        </button>
        <a href="{{ $action ?: request()->url() }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </div>
</form>
