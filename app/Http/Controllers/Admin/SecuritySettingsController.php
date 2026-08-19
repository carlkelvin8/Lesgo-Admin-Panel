<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataRetentionPolicy;
use App\Models\IpBlacklist;
use App\Models\IpWhitelist;
use App\Models\RateLimitRule;
use App\Models\SecuritySetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SecuritySettingsController extends Controller
{
    public function index()
    {
        return view('admin.security-settings.index', [
            'settings' => SecuritySetting::orderBy('category')->orderBy('setting_key')->get()->groupBy('category'),
            'rateLimits' => RateLimitRule::orderByDesc('priority')->get(),
            'whitelist' => IpWhitelist::latest()->get(),
            'blacklist' => IpBlacklist::latest()->get(),
            'retentionPolicies' => DataRetentionPolicy::orderBy('category')->orderBy('data_type')->get(),
        ]);
    }

    public function updateSetting(Request $request, SecuritySetting $setting)
    {
        $validated = $request->validate(['setting_value' => ['required', 'string', 'max:5000']]);

        if ($setting->data_type === 'boolean' && ! in_array($validated['setting_value'], ['0', '1'], true)) {
            throw ValidationException::withMessages(['setting_value' => 'Boolean settings must be enabled or disabled.']);
        }

        if ($setting->data_type === 'integer' && filter_var($validated['setting_value'], FILTER_VALIDATE_INT) === false) {
            throw ValidationException::withMessages(['setting_value' => 'This setting requires a whole number.']);
        }

        if ($setting->data_type === 'integer' && (int) $validated['setting_value'] < 1) {
            throw ValidationException::withMessages(['setting_value' => 'This setting must be at least 1.']);
        }

        if ($setting->data_type === 'json' && json_decode($validated['setting_value'], true) === null && json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages(['setting_value' => 'This setting requires valid JSON.']);
        }

        if ($setting->setting_key === '2fa_required_for_admin' && $validated['setting_value'] === '1') {
            throw ValidationException::withMessages([
                'setting_value' => 'Two-factor enforcement cannot be enabled until an admin challenge provider is configured.',
            ]);
        }

        if ($setting->setting_key === 'ip_whitelist_enabled' && $validated['setting_value'] === '1') {
            $rules = IpWhitelist::effective()->get();

            if ($rules->isEmpty()) {
                throw ValidationException::withMessages(['setting_value' => 'Add at least one active IP whitelist entry before enabling whitelist enforcement.']);
            }

            if (! $rules->contains(fn (IpWhitelist $rule) => $this->ruleCoversIp($request->ip(), $rule->ip_address, $rule->ip_range))) {
                throw ValidationException::withMessages(['setting_value' => 'Your current IP must be covered by an active whitelist rule before enforcement can be enabled.']);
            }
        }

        $setting->update([
            'setting_value' => $validated['setting_value'],
            'updated_by' => $request->user()->email,
        ]);

        return back()->with('success', 'Security setting updated.');
    }

    public function updateRateLimit(Request $request, RateLimitRule $rateLimit)
    {
        $validated = $request->validate([
            'max_attempts' => ['required', 'integer', 'min:1', 'max:100000'],
            'window_minutes' => ['required', 'integer', 'min:1', 'max:10080'],
            'priority' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $rateLimit->update($validated);

        return back()->with('success', 'Rate-limit rule updated.');
    }

    public function storeIpRule(Request $request)
    {
        $validated = $request->validate([
            'list' => ['required', 'in:whitelist,blacklist'],
            'ip_address' => ['required', 'ip'],
            'ip_range' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'type' => ['nullable', 'required_if:list,whitelist', Rule::in(['permanent', 'temporary', 'api_access'])],
            'reason' => ['nullable', 'required_if:list,blacklist', Rule::in(['suspicious_activity', 'abuse', 'security_threat'])],
        ]);

        if (filled($validated['ip_range'] ?? null) && ! $this->isValidCidr($validated['ip_range'])) {
            throw ValidationException::withMessages(['ip_range' => 'Enter a valid IPv4 or IPv6 CIDR range, such as 192.168.1.0/24.']);
        }

        if ($validated['list'] === 'blacklist' && $this->ruleCoversIp($request->ip(), $validated['ip_address'], $validated['ip_range'] ?? null)) {
            throw ValidationException::withMessages(['ip_address' => 'You cannot blacklist the IP address used by your current administrator session.']);
        }

        $attributes = [
            'ip_address' => $validated['ip_address'],
            'ip_range' => $validated['ip_range'] ?? null,
            'description' => $validated['description'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => $request->user()->email,
        ];

        if ($validated['list'] === 'whitelist') {
            IpWhitelist::create([...$attributes, 'type' => $validated['type']]);
        } else {
            IpBlacklist::create([...$attributes, 'reason' => $validated['reason']]);
        }

        return back()->with('success', 'IP access rule created.');
    }

    public function toggleIpRule(Request $request, string $list, int $ruleId)
    {
        $rule = $this->resolveIpRule($list, $ruleId);

        if ($list === 'blacklist' && ! $rule->is_active && $this->ruleCoversIp($request->ip(), $rule->ip_address, $rule->ip_range)) {
            throw ValidationException::withMessages(['ip_address' => 'You cannot activate a blacklist rule covering your current IP address.']);
        }

        if ($list === 'whitelist' && $rule->is_active && $this->whitelistEnabled() && ! $this->whitelistAllowsAfterRemoving($request->ip(), $rule)) {
            throw ValidationException::withMessages(['ip_address' => 'This change would remove whitelist access for your current IP. Disable enforcement first.']);
        }

        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', 'IP access rule status updated.');
    }

    public function destroyIpRule(Request $request, string $list, int $ruleId)
    {
        $rule = $this->resolveIpRule($list, $ruleId);

        if ($list === 'whitelist' && $rule->is_active && $this->whitelistEnabled() && ! $this->whitelistAllowsAfterRemoving($request->ip(), $rule)) {
            throw ValidationException::withMessages(['ip_address' => 'This change would remove whitelist access for your current IP. Disable enforcement first.']);
        }

        $rule->delete();

        return back()->with('success', 'IP access rule removed.');
    }

    public function updateRetention(Request $request, DataRetentionPolicy $retentionPolicy)
    {
        $validated = $request->validate([
            'retention_days' => ['required', 'integer', 'min:1', 'max:36500'],
            'deletion_method' => ['required', Rule::in(['soft_delete', 'hard_delete', 'anonymize'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $retentionPolicy->update($validated);

        return back()->with('success', 'Data-retention policy updated.');
    }

    private function resolveIpRule(string $list, int $ruleId): Model
    {
        return match ($list) {
            'whitelist' => IpWhitelist::findOrFail($ruleId),
            'blacklist' => IpBlacklist::findOrFail($ruleId),
            default => abort(404),
        };
    }

    private function whitelistEnabled(): bool
    {
        return SecuritySetting::value('ip_whitelist_enabled', false);
    }

    private function whitelistAllowsAfterRemoving(?string $ip, IpWhitelist $removedRule): bool
    {
        return IpWhitelist::effective()
            ->where($removedRule->getKeyName(), '!=', $removedRule->getKey())
            ->get()
            ->contains(fn (IpWhitelist $rule) => $this->ruleCoversIp($ip, $rule->ip_address, $rule->ip_range));
    }

    private function isValidCidr(string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $binary = @inet_pton($subnet);

        if ($binary === false || filter_var($prefix, FILTER_VALIDATE_INT) === false) {
            return false;
        }

        $prefix = (int) $prefix;

        return $prefix >= 0 && $prefix <= strlen($binary) * 8;
    }

    private function ruleCoversIp(?string $ip, string $exactIp, ?string $cidr): bool
    {
        if (blank($ip)) {
            return false;
        }

        if ($ip === $exactIp) {
            return true;
        }

        if (blank($cidr) || ! $this->isValidCidr($cidr)) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $prefix = (int) $prefix;
        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($subnetBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($subnetBinary[$fullBytes]) & $mask);
    }
}
