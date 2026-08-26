<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;

class SystemHealthController extends Controller
{
    public function index()
    {
        $checks = [];

        // Database
        try {
            DB::connection()->getPdo();
            $checks[] = ['name' => 'Database Connection', 'status' => 'healthy', 'message' => 'Connected successfully'];
        } catch (\Throwable $e) {
            $checks[] = ['name' => 'Database Connection', 'status' => 'critical', 'message' => $e->getMessage()];
        }

        // Cache
        try {
            Cache::put('health:check', true, 10);
            $value = Cache::get('health:check');
            $checks[] = ['name' => 'Cache', 'status' => $value ? 'healthy' : 'warning', 'message' => $value ? 'Working correctly' : 'Read/write mismatch'];
        } catch (\Throwable $e) {
            $checks[] = ['name' => 'Cache', 'status' => 'critical', 'message' => $e->getMessage()];
        }

        // Queue
        try {
            $queueSize = Queue::size();
            $checks[] = ['name' => 'Queue', 'status' => $queueSize > 100 ? 'warning' : 'healthy', 'message' => "{$queueSize} job(s) pending"];
        } catch (\Throwable $e) {
            $checks[] = ['name' => 'Queue', 'status' => 'critical', 'message' => $e->getMessage()];
        }

        // Storage
        $diskFree = @disk_free_space(storage_path());
        if ($diskFree !== false) {
            $freeGB = round($diskFree / 1073741824, 2);
            $checks[] = ['name' => 'Disk Space', 'status' => $freeGB < 1 ? 'critical' : ($freeGB < 5 ? 'warning' : 'healthy'), 'message' => "{$freeGB} GB free"];
        }

        // PHP Memory
        $memLimit = ini_get('memory_limit');
        $memUsed = round(memory_get_usage(true) / 1048576, 1);
        $checks[] = ['name' => 'PHP Memory', 'status' => 'healthy', 'message' => "{$memUsed}MB used / {$memLimit} limit"];

        // Uptime
        $checks[] = ['name' => 'Application', 'status' => 'healthy', 'message' => 'Running on ' . PHP_VERSION];

        return view('admin.system-health.index', compact('checks'));
    }
}
