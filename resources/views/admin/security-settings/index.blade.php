@extends('admin.layouts.app')
@section('title', 'Security Center - LesGo Admin')
@section('header', 'Security Center')

@section('content')
<div class="mb-6 rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm text-purple-900">
    <p class="font-semibold"><i class="fas fa-shield-halved mr-2"></i>Production safety controls</p>
    <p class="mt-1 text-purple-700">Whitelist enforcement cannot be enabled until an active whitelist entry exists. Blacklist rules apply immediately to administrator access.</p>
</div>

<section class="rounded-xl bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold">Security settings</h3>
    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse($settings as $category => $categorySettings)
            <div class="rounded-xl border p-4"><p class="mb-3 text-xs font-semibold uppercase tracking-wide text-purple-700">{{ $category }}</p>
                <div class="space-y-4">
                    @foreach($categorySettings as $setting)
                        <form method="POST" action="{{ route('admin.security-settings.settings.update', $setting) }}" class="rounded-lg bg-gray-50 p-3">@csrf @method('PATCH')
                            <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-medium">{{ str_replace('_', ' ', ucfirst($setting->setting_key)) }}</p><p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p></div>@if($setting->requires_restart)<span class="rounded bg-orange-100 px-2 py-1 text-[10px] text-orange-700">Restart</span>@endif</div>
                            <div class="mt-3 flex gap-2">
                                @if($setting->data_type === 'boolean')
                                    <select name="setting_value" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm"><option value="1" @selected($setting->setting_value === '1')>Enabled</option><option value="0" @selected($setting->setting_value !== '1')>Disabled</option></select>
                                @else
                                    <input name="setting_value" value="{{ $setting->setting_value }}" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm" @if($setting->data_type === 'integer') type="number" min="1" @endif>
                                @endif
                                <button class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white">Save</button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Run <code>php artisan db:seed --class=SecuritySettingsSeeder</code> to install the default security policies.</p>
        @endforelse
    </div>
</section>

<section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold">Rate-limit policies</h3>
    <p class="mt-1 text-sm text-gray-500">These records are maintained for the mobile/API layer. Admin sign-in limits are enforced directly from the security settings above.</p>
    <div class="mt-4 overflow-x-auto"><table class="responsive-table w-full text-sm"><thead class="border-b bg-gray-50"><tr><th class="px-4 py-3 text-left">Rule</th><th class="px-4 py-3 text-left">Pattern</th><th class="px-4 py-3 text-left">Limits</th><th class="px-4 py-3 text-left">Priority</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y">
        @forelse($rateLimits as $rule)
            <tr><td class="px-4 py-3"><p class="font-medium">{{ $rule->name }}</p><p class="text-xs text-gray-500">{{ $rule->scope }}</p></td><td class="px-4 py-3 font-mono text-xs">{{ $rule->method ?: 'ALL' }} {{ $rule->endpoint_pattern }}</td><td colspan="3" class="px-4 py-3"><form method="POST" action="{{ route('admin.security-settings.rate-limits.update', $rule) }}" class="flex flex-wrap items-center justify-end gap-2">@csrf @method('PATCH')<input type="number" name="max_attempts" value="{{ $rule->max_attempts }}" min="1" class="w-24 rounded border px-2 py-1.5" title="Maximum attempts"><span class="text-xs text-gray-400">per</span><input type="number" name="window_minutes" value="{{ $rule->window_minutes }}" min="1" class="w-20 rounded border px-2 py-1.5" title="Window minutes"><span class="text-xs text-gray-400">min</span><input type="number" name="priority" value="{{ $rule->priority }}" min="0" class="w-20 rounded border px-2 py-1.5" title="Priority"><select name="is_active" class="rounded border px-2 py-1.5"><option value="1" @selected($rule->is_active)>Active</option><option value="0" @selected(!$rule->is_active)>Disabled</option></select><button class="rounded bg-gray-900 px-3 py-1.5 text-xs text-white">Save</button></form></td></tr>
        @empty<tr><td colspan="5"><x-empty-state icon="fa-shield-halved" title="No rate-limit rules" description="No rate-limit rules are configured." /></td></tr>@endforelse
    </tbody></table></div>
</section>

<section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
    @foreach(['whitelist' => $whitelist, 'blacklist' => $blacklist] as $listName => $rules)
        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold">IP {{ ucfirst($listName) }}</h3>
            <form method="POST" action="{{ route('admin.security-settings.ip-rules.store') }}" class="mt-4 grid grid-cols-2 gap-3">@csrf<input type="hidden" name="list" value="{{ $listName }}"><input name="ip_address" type="text" required placeholder="IP address" class="rounded-lg border px-3 py-2 text-sm"><input name="ip_range" placeholder="Optional CIDR range" class="rounded-lg border px-3 py-2 text-sm">@if($listName === 'whitelist')<select name="type" class="rounded-lg border px-3 py-2 text-sm"><option value="permanent">Permanent</option><option value="temporary">Temporary</option><option value="api_access">API access</option></select>@else<select name="reason" class="rounded-lg border px-3 py-2 text-sm"><option value="suspicious_activity">Suspicious activity</option><option value="abuse">Abuse</option><option value="security_threat">Security threat</option></select>@endif<input type="datetime-local" name="expires_at" class="rounded-lg border px-3 py-2 text-sm"><input name="description" placeholder="Description" class="col-span-2 rounded-lg border px-3 py-2 text-sm"><button class="col-span-2 rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Add {{ $listName }} rule</button></form>
            <div class="mt-5 divide-y rounded-lg border">
                @forelse($rules as $rule)
                    <div class="flex items-center justify-between gap-3 p-3"><div class="min-w-0"><p class="font-mono text-sm">{{ $rule->ip_address }} @if($rule->ip_range)<span class="text-gray-400">/ {{ $rule->ip_range }}</span>@endif</p><p class="truncate text-xs text-gray-500">{{ $rule->description ?: ($rule->type ?? $rule->reason) }} · {{ $rule->is_active ? 'Active' : 'Disabled' }} @if($rule->expires_at)· expires {{ $rule->expires_at->format('M d, Y H:i') }}@endif</p></div><div class="flex gap-2"><form method="POST" action="{{ route('admin.security-settings.ip-rules.toggle', [$listName, $rule->id]) }}">@csrf @method('PATCH')<button class="text-xs font-semibold text-blue-700">{{ $rule->is_active ? 'Disable' : 'Enable' }}</button></form><form method="POST" action="{{ route('admin.security-settings.ip-rules.destroy', [$listName, $rule->id]) }}" onsubmit="return confirm('Remove this IP access rule?')">@csrf @method('DELETE')<button class="text-xs font-semibold text-red-600">Delete</button></form></div></div>
                @empty<p class="p-4 text-center text-sm text-gray-500">No entries.</p>@endforelse
            </div>
        </div>
    @endforeach
</section>

<section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
    <h3 class="text-lg font-semibold">Data-retention policies</h3>
    <p class="mt-1 text-sm text-gray-500">Retention records define policy only; review them before connecting an automated deletion or anonymization job.</p>
    <div class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2">
        @forelse($retentionPolicies as $policy)
            <form method="POST" action="{{ route('admin.security-settings.retention.update', $policy) }}" class="rounded-xl border p-4">@csrf @method('PATCH')<p class="font-medium">{{ str_replace('_', ' ', ucfirst($policy->data_type)) }}</p><p class="text-xs text-gray-500">{{ $policy->category }} · {{ $policy->description }}</p><div class="mt-3 flex flex-wrap gap-2"><input type="number" name="retention_days" value="{{ $policy->retention_days }}" min="1" class="w-28 rounded border px-2 py-1.5"><select name="deletion_method" class="rounded border px-2 py-1.5">@foreach(['soft_delete','hard_delete','anonymize'] as $method)<option value="{{ $method }}" @selected($policy->deletion_method === $method)>{{ str_replace('_', ' ', ucfirst($method)) }}</option>@endforeach</select><select name="is_active" class="rounded border px-2 py-1.5"><option value="1" @selected($policy->is_active)>Active</option><option value="0" @selected(!$policy->is_active)>Disabled</option></select><button class="rounded bg-gray-900 px-3 py-1.5 text-xs text-white">Save</button></div></form>
        @empty<p class="text-sm text-gray-500">No retention policies configured.</p>@endforelse
    </div>
</section>
@endsection
