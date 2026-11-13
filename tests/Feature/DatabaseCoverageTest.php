<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partner;
use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class DatabaseCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]));
    }

    public function test_live_partner_roles_and_statuses_can_be_preserved_when_editing(): void
    {
        $owner = User::factory()->create(['role' => 'partner_admin']);
        $this->post(route('admin.partners.store'), ['user_id' => $owner->id, 'name' => 'Live-role kitchen'])->assertSessionHas('success');
        $partner = Partner::firstOrFail();
        foreach (['active', 'closed'] as $status) {
            $partner->update(['status' => $status]);
            $this->get(route('admin.partners.edit', $partner))->assertSee('value="'.$status.'"', false);
            $this->put(route('admin.partners.update', $partner), ['name' => 'Updated kitchen', 'status' => $status])->assertSessionHas('success');
            $this->assertSame($status, $partner->fresh()->status);
        }
        $this->get(route('admin.users.edit', $owner))->assertSee('value="partner_admin"', false);
        $this->put(route('admin.users.update', $owner), ['name' => 'Updated owner', 'email' => $owner->email, 'role' => 'partner_admin', 'is_active' => 1])->assertSessionHas('success');
        $this->assertSame('partner_admin', $owner->fresh()->role);
    }

    public function test_deleted_accounts_are_explicitly_browsable_and_preserved_in_order_history(): void
    {
        $customer = User::factory()->create(['name' => 'Historical customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $customer->delete();
        $this->get(route('admin.users.index'))->assertDontSee('Historical customer');
        $this->get(route('admin.users.index', ['scope' => 'deleted']))->assertSee('Historical customer');
        $this->get(route('admin.users.show', $customer->id))->assertOk()->assertSee('Deleted account');
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Historical customer')->assertSee('Deleted account');
    }

    public function test_legacy_two_factor_configuration_is_used_without_copying_credentials(): void
    {
        $admin = auth()->user();
        $secret = app(Google2FA::class)->generateSecretKey();
        $legacy = (new TwoFactorAuth)->setTable('two_factor_auth');
        $legacy->fill(['user_id' => $admin->id, 'method' => 'totp', 'is_enabled' => true, 'secret' => Crypt::encryptString($secret)])->save();
        auth()->logout();
        $this->post(route('admin.login.post'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.2fa.verify'));
        $this->post(route('admin.2fa.verify.post'), ['code' => app(Google2FA::class)->getCurrentOtp($secret)])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertDatabaseCount('two_factor_auths', 0);
        $this->post(route('admin.2fa.disable'), ['password' => 'password'])->assertSessionHas('success');
        $this->assertDatabaseHas('two_factor_auth', ['id' => $legacy->id, 'is_enabled' => false]);
    }

    public function test_data_catalog_covers_the_audited_business_datasets_and_permissions(): void
    {
        $this->assertGreaterThanOrEqual(21, count(config('admin-data', [])));
        $hub = $this->get(route('admin.data.hub'))->assertOk()->assertSee('Realtime notifications')->assertSee('Registration payments');
        foreach (array_keys(config('admin-data')) as $key) {
            $hub->assertSee('href="'.route('admin.data.index', $key).'"', false);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin', 'admin_role' => 'finance', 'is_active' => true]));
        $this->get(route('admin.data.index', 'chat-messages'))->assertForbidden();
        $this->get(route('admin.data.index', 'driver-locations'))->assertForbidden();
        $this->get(route('admin.data.index', 'unknown-table'))->assertNotFound();
    }

    public function test_data_records_search_filter_pagination_details_and_export_are_safe(): void
    {
        $this->createCatalogFixture();
        for ($index = 1; $index <= 45; $index++) {
            DB::table('qa_business_records')->insert(['title' => $index === 1 ? '=Spreadsheet formula' : 'Record '.$index, 'status' => $index <= 30 ? 'active' : 'closed', 'secret_payload' => 'NEVER-EXPOSE-THIS', 'created_at' => '2026-01-10 12:00:00']);
        }
        $ids = [];
        for ($page = 1; $page <= 3; $page++) {
            $records = $this->get(route('admin.data.index', ['dataset' => 'qa-records', 'page' => $page]))->assertOk()->assertDontSee('NEVER-EXPOSE-THIS')->viewData('records');
            $ids = [...$ids, ...$records->pluck('id')->all()];
        }
        $this->assertCount(45, array_unique($ids));
        $this->get(route('admin.data.index', ['dataset' => 'qa-records', 'status' => 'active', 'search' => 'Record', 'date_from' => '2026-01-10', 'date_to' => '2026-01-10']))
            ->assertOk()->assertViewHas('records', fn ($records) => $records->total() === 29);
        $this->get(route('admin.data.show', ['dataset' => 'qa-records', 'id' => 1]))->assertOk()->assertSee('Spreadsheet formula')->assertDontSee('NEVER-EXPOSE-THIS');
        $csv = $this->get(route('admin.data.export', 'qa-records'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=Spreadsheet formula", $csv);
        $this->assertStringNotContainsString('NEVER-EXPOSE-THIS', $csv);
        $this->post(route('admin.data.index', 'qa-records'), [])->assertMethodNotAllowed();
    }

    public function test_catalog_hides_missing_tables_and_rejects_invalid_query_inputs(): void
    {
        $this->createCatalogFixture();
        $this->getJson(route('admin.data.index', ['dataset' => 'qa-records', 'search' => ['invalid']]))->assertUnprocessable();
        config(['admin-data.qa-records.table' => 'table_not_installed']);
        $this->get(route('admin.data.index', 'qa-records'))->assertStatus(503)->assertSee('not available');
    }

    public function test_order_details_include_branch_fare_calculation_and_only_safe_proof_links(): void
    {
        if (! Schema::hasTable('partner_branches')) {
            Schema::create('partner_branches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->string('name');
                $table->string('address_line1')->nullable();
                $table->string('city')->nullable();
            });
        }
        foreach (['partner_branch_id', 'fare_breakdown', 'proof_images', 'proof_uploaded_at'] as $column) {
            if (! Schema::hasColumn('orders', $column)) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    if ($column === 'partner_branch_id') {
                        $table->unsignedBigInteger($column)->nullable();
                    } elseif ($column === 'proof_uploaded_at') {
                        $table->timestamp($column)->nullable();
                    } else {
                        $table->json($column)->nullable();
                    }
                });
            }
        }
        $partner = Partner::create(['name' => 'Branch owner', 'slug' => 'branch-owner']);
        $branchId = DB::table('partner_branches')->insertGetId(['partner_id' => $partner->id, 'name' => 'Riverside branch', 'address_line1' => 'QA street', 'city' => 'QA city']);
        $order = Order::factory()->create();
        $order->forceFill(['partner_branch_id' => $branchId, 'fare_breakdown' => ['distance_fee' => 125, 'discount' => 10], 'proof_images' => ['https://example.test/proof.jpg', 'javascript:alert(1)', '../secrets.png', 'storage/proofs/safe.png'], 'proof_uploaded_at' => now()])->save();
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Riverside branch')->assertSee('Distance Fee')
            ->assertSee('Delivery proof')->assertSee('https://example.test/proof.jpg')->assertSee('/storage/proofs/safe.png')
            ->assertDontSee('javascript:alert')->assertDontSee('../secrets.png');
    }

    public function test_catalog_masks_verification_values_in_details_and_csv(): void
    {
        $this->createCatalogFixture();
        Schema::table('qa_business_records', fn (Blueprint $table) => $table->string('phone')->nullable());
        config(['admin-data.qa-records.fields' => 'id,title,status,created_at,phone', 'admin-data.qa-records.masked' => 'phone']);
        DB::table('qa_business_records')->insert(['title' => 'Verification', 'status' => 'active', 'secret_payload' => 'NEVER-EXPOSE-THIS', 'phone' => '1234567890', 'created_at' => now()]);
        $this->get(route('admin.data.show', ['dataset' => 'qa-records', 'id' => 1]))->assertOk()->assertSee('••••7890')->assertDontSee('1234567890');
        $csv = $this->get(route('admin.data.export', 'qa-records'))->assertOk()->streamedContent();
        $this->assertStringContainsString('••••7890', $csv);
        $this->assertStringNotContainsString('1234567890', $csv);
        foreach (config('admin-data') as $definition) {
            $this->assertSame([], array_intersect(explode(',', $definition['fields']), ['token_hash', 'account_details_encrypted', 'password', 'secret', 'backup_codes', 'recovery_codes', 'checkout_url', 'idempotency_key']));
        }
    }

    public function test_enabled_legacy_two_factor_cannot_be_shadowed_by_a_disabled_new_record(): void
    {
        $admin = auth()->user();
        TwoFactorAuth::factory()->create(['user_id' => $admin->id, 'is_enabled' => false]);
        $secret = app(Google2FA::class)->generateSecretKey();
        (new TwoFactorAuth)->setTable('two_factor_auth')->fill(['user_id' => $admin->id, 'method' => 'totp', 'is_enabled' => true, 'secret' => Crypt::encryptString($secret)])->save();
        $this->assertSame('two_factor_auth', TwoFactorAuth::forUser($admin->id, true)->getTable());
        $this->post(route('admin.2fa.disable'), ['password' => 'password'])->assertSessionHas('success');
        $this->assertNull(TwoFactorAuth::forUser($admin->id, true));
    }

    private function createCatalogFixture(): void
    {
        Schema::create('qa_business_records', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->string('secret_payload');
            $table->timestamp('created_at');
        });
        config(['admin-data.qa-records' => ['title' => 'QA records', 'group' => 'QA', 'table' => 'qa_business_records', 'permission' => 'users.view', 'fields' => 'id,title,status,created_at', 'list' => 'id,title,status,created_at', 'search' => 'title', 'status' => 'status', 'date' => 'created_at']]);
    }
}
