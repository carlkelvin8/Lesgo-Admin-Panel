<?php

namespace App\Services;

use App\Models\IpBlacklist;
use App\Models\IpWhitelist;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminNetworkAccess
{
    public function allows(?string $ip): bool
    {
        if (blank($ip)) {
            return true;
        }

        try {
            if (! Schema::hasTable('ip_blacklist') || ! Schema::hasTable('ip_whitelist') || ! Schema::hasTable('security_settings')) {
                return true;
            }

            if ($this->matches(IpBlacklist::effective()->get(), $ip)) {
                return false;
            }

            if (! SecuritySetting::value('ip_whitelist_enabled', false)) {
                return true;
            }

            return $this->matches(IpWhitelist::effective()->get(), $ip);
        } catch (Throwable $exception) {
            report($exception);

            return true;
        }
    }

    private function matches(iterable $rules, string $ip): bool
    {
        foreach ($rules as $rule) {
            if ($rule->ip_address === $ip || (filled($rule->ip_range) && $this->inCidr($ip, $rule->ip_range))) {
                return true;
            }
        }

        return false;
    }

    private function inCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $prefix] = explode('/', $cidr, 2);
        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $prefix = (int) $prefix;
        $maxBits = strlen($ipBinary) * 8;
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

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
