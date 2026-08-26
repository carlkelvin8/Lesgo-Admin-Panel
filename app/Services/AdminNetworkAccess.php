<?php

namespace App\Services;

use App\Models\IpBlacklist;
use App\Models\IpWhitelist;
use App\Models\SecuritySetting;
use App\Traits\CidrMatching;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminNetworkAccess
{
    use CidrMatching;

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
}
