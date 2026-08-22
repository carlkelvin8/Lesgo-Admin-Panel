@props(['paginator'])

@if ($paginator->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 py-4">
        <p class="text-sm text-gray-600">
            Showing <span class="font-medium">{{ $paginator->firstItem() }}</span>
            to <span class="font-medium">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium">{{ $paginator->total() }}</span> results
        </p>
        <div>
            {{ $paginator->links() }}
        </div>
    </div>
@endif
