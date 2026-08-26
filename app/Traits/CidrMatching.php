<?php

declare(strict_types=1);

namespace App\Traits;

trait CidrMatching
{
    public function isValidCidr(string $cidr): bool
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

    public function inCidr(string $ip, string $cidr): bool
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

    public function ruleCoversIp(?string $ip, string $exactIp, ?string $cidr): bool
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

        return $this->inCidr($ip, $cidr);
    }
}
