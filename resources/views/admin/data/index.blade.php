@extends('admin.layouts.app')
@section('title', $definition['title'].' - LesGo Admin')
@section('header', $definition['title'])
@section('actions')
<a href="{{ route('admin.data.hub') }}" class="rounded-lg border px-4 py-2 text-sm">All business records</a>
<a href="{{ route('admin.data.export', ['dataset' => $definition['key'], ...request()->only(['search','status','date_from','date_to', ...$definition['relations']])]) }}" class="rounded-lg bg-green-700 px-4 py-2 text-sm text-white">Export CSV</a>
@endsection
@section('content')
@if(isset($definition['notice']))<p class="mb-4 rounded-lg bg-purple-50 p-4 text-sm text-purple-800">{{ $definition['notice'] }}</p>@endif
<x-filter-panel :action="route('admin.data.index', $definition['key'])">
<x-filter-input name="search" label="Search" placeholder="Record ID or text" />
@if($definition['status'])<x-filter-input name="status" label="Status" placeholder="Status value" />@endif
@foreach($definition['relations'] as $field)<x-filter-input :name="$field" :label="\Illuminate\Support\Str::headline($field)" placeholder="ID" />@endforeach
@if($definition['date'])<x-filter-input name="date_from" label="From" type="date" /><x-filter-input name="date_to" label="To" type="date" />@endif
</x-filter-panel>
<div class="overflow-hidden rounded-xl bg-white shadow-sm"><div class="overflow-x-auto">
<table class="responsive-table w-full text-sm"><thead class="border-b bg-gray-50"><tr>
@foreach($definition['list'] as $field)<th class="px-4 py-3 text-left font-medium text-gray-500">{{ \Illuminate\Support\Str::headline($field) }}</th>@endforeach
<th class="px-4 py-3 text-right">Details</th></tr></thead><tbody class="divide-y">
@forelse($records as $record)<tr class="hover:bg-gray-50">
@foreach($definition['list'] as $field)<td class="max-w-xs px-4 py-3" data-label="{{ \Illuminate\Support\Str::headline($field) }}"><span class="block truncate">{{ $catalog->display($definition, $field, $record->$field) }}</span></td>@endforeach
<td class="px-4 py-3 text-right"><a href="{{ route('admin.data.show', ['dataset' => $definition['key'], 'id' => $record->id]) }}" class="font-medium text-purple-700">View <span class="sr-only">record {{ $record->id }}</span></a></td></tr>
@empty<tr><td colspan="{{ count($definition['list']) + 1 }}" class="px-6 py-10 text-center text-gray-500">No matching records.</td></tr>@endforelse
</tbody></table></div><div class="border-t p-4">{{ $records->links() }}</div></div>
<p class="mt-3 text-sm text-gray-500">{{ number_format($records->total()) }} matching records · Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</p>
@endsection
