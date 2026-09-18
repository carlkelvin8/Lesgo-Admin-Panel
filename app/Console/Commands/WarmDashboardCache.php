<?php

namespace App\Console\Commands;

use App\Services\DashboardService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Proactively populate (or refresh) the admin dashboard cache so the first
 * human page-load is instant rather than cold.
 *
 * Usage:
 *   php artisan admin:warm-dashboard          # warms default 7-day window
 *   php artisan admin:warm-dashboard --days=30
 *   php artisan admin:warm-dashboard --flush  # busts existing cache first
 *
 * Recommended cron entry (in routes/console.php or a Scheduler definition):
 *   Schedule::command('admin:warm-dashboard')->everyFiveMinutes();
 */
class WarmDashboardCache extends Command
{
    protected $signature = 'admin:warm-dashboard
                            {--days=7 : Number of days of historical data to warm (1–90)}
                            {--flush  : Bust all dashboard cache keys before warming}';

    protected $description = 'Pre-populate the admin dashboard cache to eliminate cold-load latency.';

    public function handle(DashboardService $service): int
    {
        $days = (int) $this->option('days');
        $days = max(1, min(90, $days));

        if ($this->option('flush')) {
            $this->line('Flushing existing dashboard cache…');
            $service->flushCache();
            $this->info('Cache flushed.');
        }

        $this->line("Warming dashboard cache for last {$days} day(s)…");

        $start = microtime(true);
        try {
            $service->getAll($days);
        } catch (Throwable $e) {
            $this->error('Cache warm failed: '.$e->getMessage());
            report($e);
            return self::FAILURE;
        }
        $elapsed = round((microtime(true) - $start) * 1000);

        $this->info("Dashboard cache warmed successfully in {$elapsed} ms.");
        return self::SUCCESS;
    }
}
