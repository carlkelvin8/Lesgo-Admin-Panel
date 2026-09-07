<?php

namespace App\Services;

use App\Models\DriverProfile;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CascadeEntityDeletionService
{
    public function deleteDriver(DriverProfile $driver): void
    {
        DB::transaction(function () use ($driver) {
            $user = $driver->user;
            $this->deleteOrders(DB::table('orders')->where('driver_id', $driver->id)->pluck('id'));
            $this->deleteWhere('payments', 'driver_id', $driver->id);

            $driver->delete();

            if ($user) {
                $user->forceDelete();
            }
        });
    }

    public function deletePartner(Partner $partner): void
    {
        DB::transaction(function () use ($partner) {
            $owner = $partner->user;

            DriverProfile::query()
                ->where('partner_id', $partner->id)
                ->get()
                ->each(fn (DriverProfile $driver) => $this->deleteDriver($driver));

            $this->deleteOrders(DB::table('orders')->where('partner_id', $partner->id)->pluck('id'));
            $this->deleteWhere('payments', 'partner_id', $partner->id);
            $this->deleteWhere('revenue_analytics', 'partner_id', $partner->id);
            $this->deleteWhere('services', 'partner_id', $partner->id);

            $partner->delete();

            if ($owner && ! Partner::query()->where('user_id', $owner->id)->exists()) {
                $owner->forceDelete();
            }
        });
    }

    private function deleteOrders(iterable $orderIds): void
    {
        $ids = collect($orderIds)->values();
        if ($ids->isEmpty()) {
            return;
        }

        // Payments do not have an ON DELETE CASCADE constraint. Other order-owned
        // records do, but deleting them explicitly also supports older databases.
        foreach (['payments', 'ratings_reviews', 'order_tracking_events', 'social_shares',
            'geofence_events', 'chat_conversations', 'lesbuy_items', 'order_items'] as $table) {
            $this->deleteWhereIn($table, 'order_id', $ids);
        }

        DB::table('orders')->whereIn('id', $ids)->delete();
    }

    private function deleteWhere(string $table, string $column, int $value): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->where($column, $value)->delete();
        }
    }

    private function deleteWhereIn(string $table, string $column, $values): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->whereIn($column, $values)->delete();
        }
    }
}
