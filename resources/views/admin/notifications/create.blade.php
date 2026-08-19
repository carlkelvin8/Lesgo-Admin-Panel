@extends('admin.layouts.app')
@section('title', 'Publish Notification - LesGo Admin')
@section('header', 'Publish Notification')

@section('content')
<form method="POST" action="{{ route('admin.notifications.store') }}" class="max-w-3xl bg-white rounded-xl shadow-sm p-6 space-y-5">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Audience type</label>
            <select name="recipient_type" id="recipient_type" class="w-full border border-gray-300 rounded-lg px-3 py-2" onchange="toggleRecipient()">
                <option value="user" @selected(old('recipient_type') === 'user')>Specific user</option>
                <option value="role" @selected(old('recipient_type') === 'role')>User group</option>
            </select>
        </div>
        <div id="user_recipient">
            <label class="block text-sm font-medium mb-1">User</label>
            <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <option value="">Select user</option>
                @foreach($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} — {{ $user->email }} ({{ $user->role }})</option>@endforeach
            </select>
        </div>
        <div id="role_recipient" class="hidden">
            <label class="block text-sm font-medium mb-1">User group</label>
            <select name="recipient_role" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @foreach(['all'=>'All active users','customer'=>'Customers','driver'=>'Drivers','partner'=>'Partners','admin'=>'Admins'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('recipient_role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><label class="block text-sm font-medium mb-1">Type</label><input name="type" value="{{ old('type', 'admin.announcement') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="admin.announcement"></div>
        <div><label class="block text-sm font-medium mb-1">Channel</label><select name="channel" class="w-full border border-gray-300 rounded-lg px-3 py-2">@foreach(['in_app','push','sms','email'] as $channel)<option value="{{ $channel }}" @selected(old('channel', 'in_app') === $channel)>{{ strtoupper(str_replace('_', ' ', $channel)) }}</option>@endforeach</select><p class="mt-1 text-xs text-gray-500">Push, SMS, and email are delivered by the notification queue and tracked per recipient.</p></div>
    </div>
    <div><label class="block text-sm font-medium mb-1">Title</label><input name="title" value="{{ old('title') }}" required maxlength="255" class="w-full border border-gray-300 rounded-lg px-3 py-2"></div>
    <div><label class="block text-sm font-medium mb-1">Message</label><textarea name="body" rows="6" required class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('body') }}</textarea></div>
    <div><label class="block text-sm font-medium mb-1">Optional JSON data</label><textarea name="data" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm" placeholder='{"order_id": 123}'>{{ old('data') }}</textarea></div>
    <div class="flex gap-3"><button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg">Publish</button><a href="{{ route('admin.notifications.index') }}" class="px-5 py-2 border rounded-lg">Cancel</a></div>
</form>
@endsection

@section('scripts')
<script>
function toggleRecipient() {
    const byRole = document.getElementById('recipient_type').value === 'role';
    document.getElementById('user_recipient').classList.toggle('hidden', byRole);
    document.getElementById('role_recipient').classList.toggle('hidden', !byRole);
}
toggleRecipient();
</script>
@endsection
