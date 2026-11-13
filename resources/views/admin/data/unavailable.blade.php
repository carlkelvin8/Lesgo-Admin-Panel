@extends('admin.layouts.app')
@section('title', $definition['title'].' - LesGo Admin')
@section('header', $definition['title'])
@section('content')
<div class="rounded-xl bg-white p-8 shadow-sm"><h3 class="font-semibold">Records not available</h3><p class="mt-2 text-sm text-gray-500">These records are not available in this environment.</p><a href="{{ route('admin.data.hub') }}" class="mt-4 inline-block text-purple-700">Back to business records</a></div>
@endsection
