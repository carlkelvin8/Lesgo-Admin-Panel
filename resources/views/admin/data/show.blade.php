@extends('admin.layouts.app')
@section('title', $definition['title'].' #'.$record->id.' - LesGo Admin')
@section('header', $definition['title'].' #'.$record->id)
@section('actions')<a href="{{ route('admin.data.index', $definition['key']) }}" class="rounded-lg border px-4 py-2 text-sm">Back to records</a>@endsection
@section('content')
@if(isset($definition['notice']))<p class="mb-4 rounded-lg bg-purple-50 p-4 text-sm text-purple-800">{{ $definition['notice'] }}</p>@endif
<dl class="max-w-5xl divide-y rounded-xl bg-white p-6 shadow-sm">
@foreach($definition['fields'] as $field)
<div class="grid gap-2 py-4 sm:grid-cols-[200px_1fr]"><dt class="text-sm font-medium text-gray-500">{{ \Illuminate\Support\Str::headline($field) }}</dt><dd class="whitespace-pre-wrap break-words text-sm text-gray-900">{{ $catalog->display($definition, $field, $record->$field) }}</dd></div>
@endforeach
</dl>
@endsection
