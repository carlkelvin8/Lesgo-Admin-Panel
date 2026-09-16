<?php

namespace Tests\Unit\Services;

use App\Models\Partner;
use App\Services\AuditActionResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditActionResolverTest extends TestCase
{
    private function resolve(
        string $action,
        ?Partner $resource = null,
        array $newValues = [],
        ?array $oldValues = null
    ): array {
        return app(AuditActionResolver::class)
            ->resolve(Request::create('/admin/partners', 'POST'), $resource, $newValues, $oldValues, $action);
    }

    private function partner(int $id, string $name): Partner
    {
        $partner = new Partner(['name' => $name]);
        $partner->id = $id;

        return $partner;
    }

    public function test_create_describes_entity(): void
    {
        $resolved = $this->resolve('admin.partners.store', null, [
            'name' => 'Jollibee',
            'delivery_fee' => 50,
        ]);

        $this->assertSame("Created partner 'Jollibee'", $resolved['description']);
        $this->assertSame('data_modification', $resolved['event_category']);
        $this->assertSame('low', $resolved['risk_level']);
    }

    public function test_update_lists_changed_fields_and_references_model(): void
    {
        $resolved = $this->resolve('admin.partners.update', $this->partner(12, 'Jollibee'), [
            'status' => 'active',
            'delivery_fee' => 60,
        ], [
            'status' => 'pending',
            'delivery_fee' => 50,
        ]);

        $this->assertSame("Updated partner 'Jollibee' (#12) — changed: status, delivery fee", $resolved['description']);
    }

    public function test_destroy_is_high_risk(): void
    {
        $resolved = $this->resolve('admin.partners.destroy', $this->partner(7, 'McDo'));

        $this->assertSame("Deleted partner 'McDo' (#7)", $resolved['description']);
        $this->assertSame('high', $resolved['risk_level']);
    }

    public function test_bulk_destroy_lists_ids(): void
    {
        $resolved = $this->resolve('admin.partners.bulk-destroy', null, [
            'ids' => [1, 2, 3, 99],
        ]);

        $this->assertSame('Bulk-deleted 4 partners (IDs: 1, 2, 3, 99)', $resolved['description']);
        $this->assertSame('high', $resolved['risk_level']);
    }

    public function test_toggle_describes_status_change(): void
    {
        $resolved = $this->resolve('admin.partners.toggle', $this->partner(3, 'Chowking'));

        $this->assertSame("Toggled partner status 'Chowking' (#3)", $resolved['description']);
    }

    public function test_logout_special_action(): void
    {
        $resolved = $this->resolve('admin.logout');

        $this->assertSame('Signed out of the admin panel', $resolved['description']);
        $this->assertSame('authentication', $resolved['event_category']);
    }

    public function test_security_settings_are_security_high_risk(): void
    {
        $resolved = $this->resolve('admin.security-settings.ip-rules.store');

        $this->assertSame('Added an IP allow/deny rule', $resolved['description']);
        $this->assertSame('security', $resolved['event_category']);
        $this->assertSame('high', $resolved['risk_level']);
    }

    public function test_role_permission_update_is_authorization(): void
    {
        $resolved = $this->resolve('admin.roles.update');

        $this->assertSame('Updated role permissions', $resolved['description']);
        $this->assertSame('authorization', $resolved['event_category']);
        $this->assertSame('high', $resolved['risk_level']);
    }

    public function test_waive_registration_fee(): void
    {
        $resolved = $this->resolve('admin.registration-fees.waive', null, ['id' => 8]);

        $this->assertSame("Waived registration fee (#8)", $resolved['description']);
    }
}