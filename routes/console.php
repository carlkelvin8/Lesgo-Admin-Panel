<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:diagnose', function () {
    $connection = config('database.default');
    $this->line("Default DB connection: {$connection}");
    $this->line('Driver: '.DB::connection()->getDriverName());
    try {
        $this->line('Database: '.DB::connection()->getDatabaseName());
    } catch (\Throwable $e) {
        $this->line('Database name lookup failed: '.$e->getMessage());
    }

    try {
        $this->line('payments table exists: '.(Schema::hasTable('payments') ? 'yes' : 'NO'));
        if (Schema::hasTable('payments')) {
            $this->line('payments count: '.DB::table('payments')->count());
            $this->line('latest payment id: '.(DB::table('payments')->orderByDesc('id')->value('id') ?? 'none'));
        }
    } catch (\Throwable $e) {
        $this->error('Payment query failed: '.$e->getMessage());
    }

    try {
        $this->line('partners count: '.(Schema::hasTable('partners') ? DB::table('partners')->count() : 'no table'));
    } catch (\Throwable $e) {
        $this->error('Partner query failed: '.$e->getMessage());
    }
})->purpose('Diagnose which database the admin panel is reading and whether payments exist');

// ── Dashboard cache warming ───────────────────────────────────────────────────
// Keeps the admin dashboard cache hot so every page-load hits cached data.
// Runs every 5 minutes; --flush resets stale keys once per hour.
Schedule::command('admin:warm-dashboard --days=7')->everyFiveMinutes();
Schedule::command('admin:warm-dashboard --days=7 --flush')->hourly();
