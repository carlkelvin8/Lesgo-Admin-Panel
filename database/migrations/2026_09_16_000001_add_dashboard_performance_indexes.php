<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes that accelerate the admin dashboard queries introduced by the
 * DashboardService rewrite (getTopPartners UNION ALL + getDailyRevenue).
 *
 * Deliberately guarded with hasIndex() checks so the migration is safe to
 * run on environments that already have some of these indexes applied manually.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── menu_items ────────────────────────────────────────────────────────
        // Required for the indirect partner-resolution JOIN in getTopPartners().
        Schema::table('menu_items', function (Blueprint $table) {
            // partner_id — look up all menu items for a given partner
            if (! Schema::hasIndex('menu_items', 'menu_items_partner_id_index')) {
                $table->index('partner_id', 'menu_items_partner_id_index');
            }
            // (partner_id, id) — covering index for the JOIN path:
            //   lesbuy_items.menu_item_id → menu_items.id → menu_items.partner_id
            if (! Schema::hasIndex('menu_items', 'menu_items_partner_id_id_index')) {
                $table->index(['partner_id', 'id'], 'menu_items_partner_id_id_index');
            }
        });

        // ── orders ────────────────────────────────────────────────────────────
        // Composite covering index used by both getDailyRevenue() and getTopPartners()
        // when filtering on payment_status + date range.
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasIndex('orders', 'orders_payment_status_created_at_index')) {
                $table->index(
                    ['payment_status', 'created_at'],
                    'orders_payment_status_created_at_index'
                );
            }
            // Composite for the direct-partner branch of getTopPartners():
            //   WHERE partner_id IS NOT NULL AND created_at >= ?
            if (! Schema::hasIndex('orders', 'orders_partner_id_created_at_index')) {
                $table->index(
                    ['partner_id', 'created_at'],
                    'orders_partner_id_created_at_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            if (Schema::hasIndex('menu_items', 'menu_items_partner_id_index')) {
                $table->dropIndex('menu_items_partner_id_index');
            }
            if (Schema::hasIndex('menu_items', 'menu_items_partner_id_id_index')) {
                $table->dropIndex('menu_items_partner_id_id_index');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasIndex('orders', 'orders_payment_status_created_at_index')) {
                $table->dropIndex('orders_payment_status_created_at_index');
            }
            if (Schema::hasIndex('orders', 'orders_partner_id_created_at_index')) {
                $table->dropIndex('orders_partner_id_created_at_index');
            }
        });
    }
};
