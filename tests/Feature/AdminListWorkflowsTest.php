<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\DocumentVerification;
use App\Models\DriverProfile;
use App\Models\FaqArticle;
use App\Models\FaqCategory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\RatingReview;
use App\Models\SecurityEvent;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminListWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_filter_defaults_and_record_specific_choices_are_rendered(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
        $this->get(route('admin.drivers.index'))->assertOk()->assertSee('<option value="" selected>All</option>', false);
        Payment::factory()->create(['method' => 'gcash']);
        $this->get(route('admin.payments.index'))->assertOk()->assertSee('value="gcash"', false);
        DocumentVerification::create(['user_id' => auth()->id(), 'document_type' => 'valid_id', 'document_urls' => []]);
        $this->get(route('admin.document-verifications.index'))->assertOk()->assertSee('value="valid_id"', false);
    }

    public static function exports(): array
    {
        return [['users'], ['orders'], ['payments'], ['reports']];
    }

    #[DataProvider('exports')]
    public function test_filtered_csv_matches_list_rows_including_date_boundaries(string $module): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
        $customer = User::factory()->create(['name' => 'Zoë, CSV Fixture', 'role' => 'customer', 'is_active' => true]);
        $filters = ['date_from' => '2026-11-01', 'date_to' => '2026-11-01'];
        $expected = [];
        if ($module === 'users') {
            $filters = ['search' => 'CSV Fixture', 'role' => 'customer', 'status' => 'active'];
            $expected = [(string) $customer->id];
            User::factory()->create(['name' => 'CSV Fixture', 'role' => 'driver']);
        } elseif ($module === 'orders') {
            $filters += ['status' => 'pending', 'payment_status' => 'pending', 'search' => 'CSV Fixture'];
            foreach (['00:00:00', '23:59:59'] as $time) {
                $expected[] = (string) Order::factory()->create(['customer_id' => $customer->id, 'status' => 'pending', 'payment_status' => 'pending', 'created_at' => '2026-11-01 '.$time])->id;
            }
            Order::factory()->create(['customer_id' => $customer->id, 'status' => 'cancelled', 'created_at' => '2026-11-01 12:00:00']);
        } elseif ($module === 'payments') {
            $filters += ['status' => 'paid', 'method' => 'gcash', 'search' => 'CSV Fixture'];
            foreach (['00:00:00', '23:59:59'] as $time) {
                $expected[] = (string) Payment::factory()->create(['customer_id' => $customer->id, 'status' => 'paid', 'method' => 'gcash', 'created_at' => '2026-11-01 '.$time])->id;
            }
            Payment::factory()->create(['customer_id' => $customer->id, 'status' => 'paid', 'method' => 'cash', 'created_at' => '2026-11-01 12:00:00']);
        } else {
            DailyReport::create(['report_date' => '2026-11-01']);
            DailyReport::create(['report_date' => '2026-11-02']);
            $expected = ['2026-11-01'];
        }
        $list = $this->get(route('admin.'.$module.'.index', $filters))->assertOk()->viewData($module);
        $this->assertSame(count($expected), $list->total());
        $csv = $this->get(route('admin.'.$module.'.export', $filters))->assertOk()->streamedContent();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $this->assertIsArray(fgetcsv($stream, escape: ''));
        $actual = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $actual[] = $row[0];
        }
        fclose($stream);
        sort($actual);
        sort($expected);
        $this->assertSame($expected, $actual);
    }

    public static function lists(): array
    {
        return [
            'users' => ['users.index', 'users', ['role' => 'customer']],
            'drivers' => ['drivers.index', 'drivers', ['status' => 'active']],
            'partners' => ['partners.index', 'partners', ['status' => 'active']],
            'orders' => ['orders.index', 'orders', ['status' => 'pending', 'payment_status' => 'pending']],
            'services' => ['services.index', 'services', ['search' => 'Pagination Fixture']],
            'ratings' => ['ratings.index', 'reviews', ['status' => 'approved', 'min_rating' => 4, 'max_rating' => 4]],
            'payments' => ['payments.index', 'payments', ['status' => 'paid', 'method' => 'gcash']],
            'wallets' => ['wallets.index', 'wallets', ['search' => 'Pagination Fixture']],
            'top-ups' => ['wallets.top-ups.index', 'topUps', ['status' => 'pending', 'provider' => 'qa']],
            'tickets' => ['tickets.index', 'tickets', ['status' => 'open', 'priority' => 'medium']],
            'notifications' => ['notifications.index', 'notifications', ['channel' => 'in_app', 'read_status' => 'unread']],
            'faq categories' => ['faq.categories', 'categories', []],
            'faq articles' => ['faq.articles', 'articles', ['is_published' => 1]],
            'verifications' => ['document-verifications.index', 'verifications', ['document_type' => 'valid_id', 'status' => 'pending']],
            'audit logs' => ['audit-logs.index', 'logs', ['risk_level' => 'low']],
            'security events' => ['security-events.index', 'events', ['severity' => 'high', 'status' => 'open']],
            'reports' => ['reports.index', 'reports', ['date_from' => '2026-11-02', 'date_to' => '2026-11-26']],
        ];
    }

    #[DataProvider('lists')]
    public function test_filtered_lists_paginate_without_duplicates_when_timestamps_match(string $route, string $viewKey, array $filters): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
        $customer = User::factory()->create(['name' => 'Pagination Fixture', 'role' => 'customer']);
        // The helper account itself participates in the user list only.
        $ids = $route === 'users.index' ? [$customer->id] : [];
        for ($index = 1; $index <= 25; $index++) {
            $ids[] = $this->record($route, $customer, $index)->id;
        }
        rsort($ids);
        $seen = [];
        foreach ([1, 2] as $page) {
            $records = $this->get(route('admin.'.$route, [...$filters, 'page' => $page]))
                ->assertOk()->viewData($viewKey);
            $this->assertSame(count($ids), $records->total());
            $seen = [...$seen, ...$records->pluck('id')->all()];
            parse_str(parse_url($records->url(2), PHP_URL_QUERY), $pageQuery);
            foreach ($filters as $key => $value) {
                $this->assertSame((string) $value, $pageQuery[$key]);
            }
        }
        $this->assertSame($ids, $seen, 'Rows must have stable descending ID order when timestamps tie.');
        $this->assertCount(count($ids), array_unique($seen));
        $this->get(route('admin.'.$route, [...$filters, 'page' => 3]))->assertOk()
            ->assertViewHas($viewKey, fn ($records) => $records->isEmpty());
    }

    private function record(string $route, User $user, int $index): mixed
    {
        return match ($route) {
            'users.index' => User::factory()->create(['role' => 'customer']),
            'drivers.index' => DriverProfile::create(['user_id' => User::factory()->create(['role' => 'driver'])->id, 'status' => 'active']),
            'partners.index' => Partner::create(['name' => 'Fixture', 'slug' => 'fixture-'.$index, 'status' => 'active']),
            'orders.index' => Order::factory()->create(['customer_id' => $user->id, 'status' => 'pending', 'payment_status' => 'pending']),
            'services.index' => Service::factory()->create(['name' => 'Pagination Fixture '.$index]),
            'ratings.index' => RatingReview::create(['user_id' => $user->id, 'order_id' => Order::factory()->create()->id, 'overall_rating' => 4, 'status' => 'approved']),
            'payments.index' => Payment::factory()->create(['status' => 'paid', 'method' => 'gcash']),
            'wallets.index' => Wallet::factory()->create(['user_id' => User::factory()->create(['name' => 'Pagination Fixture '.$index])->id]),
            'wallets.top-ups.index' => WalletTopUp::create(['user_id' => $user->id, 'wallet_id' => Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'currency' => 'PHP'])->id, 'amount' => 10, 'fee' => 0, 'total_charged' => 10, 'currency' => 'PHP', 'external_id' => 'QA-'.$index, 'status' => 'pending', 'provider' => 'qa', 'payment_method' => 'qa']),
            'tickets.index' => SupportTicket::create(['user_id' => $user->id, 'ticket_number' => 'QA-'.$index, 'subject' => 'Fixture', 'description' => 'Fixture', 'category' => 'other', 'status' => 'open', 'priority' => 'medium']),
            'notifications.index' => Notification::create(['user_id' => $user->id, 'type' => 'qa', 'title' => 'Fixture', 'body' => 'Fixture', 'channel' => 'in_app']),
            'faq.categories' => FaqCategory::create(['name' => 'Fixture '.$index, 'slug' => 'fixture-'.$index]),
            'faq.articles' => FaqArticle::create(['category_id' => FaqCategory::firstOrCreate(['slug' => 'fixture'], ['name' => 'Fixture'])->id, 'title' => 'Fixture '.$index, 'slug' => 'fixture-'.$index, 'content' => 'Fixture', 'is_published' => true, 'created_by' => auth()->id()]),
            'document-verifications.index' => DocumentVerification::create(['user_id' => $user->id, 'document_type' => 'valid_id', 'document_urls' => [], 'status' => 'pending', 'submitted_at' => now()]),
            'audit-logs.index' => AuditLog::create(['user_id' => auth()->id(), 'action' => 'fixture', 'risk_level' => 'low', 'occurred_at' => now()]),
            'security-events.index' => SecurityEvent::create(['event_type' => 'fixture', 'severity' => 'high', 'detected_at' => now(), 'is_resolved' => false]),
            'reports.index' => DailyReport::create(['report_date' => Carbon::parse('2026-11-01')->addDays($index)->toDateString()]),
        };
    }

    public function test_wallet_transaction_pagination_preserves_type_and_date_filters(): void
    {
        $this->freezeTime();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
        $wallet = Wallet::factory()->create();
        $ids = [];
        for ($i = 0; $i < 25; $i++) {
            $ids[] = WalletTransaction::create(['wallet_id' => $wallet->id, 'type' => 'credit', 'amount' => 1, 'balance_before' => $i, 'balance_after' => $i + 1, 'description' => 'Pagination fixture', 'reference' => 'PAGE-'.$i])->id;
        }
        $seen = [];
        foreach ([1, 2] as $page) {
            $records = $this->get(route('admin.wallets.transactions', ['wallet' => $wallet, 'type' => 'credit', 'date_from' => today()->toDateString(), 'date_to' => today()->toDateString(), 'page' => $page]))->assertOk()->viewData('transactions');
            $this->assertSame(25, $records->total());
            $seen = [...$seen, ...$records->pluck('id')->all()];
            $this->assertStringContainsString('type=credit', $records->url(2));
        }
        $this->assertSame(array_reverse($ids), $seen);
    }
}
