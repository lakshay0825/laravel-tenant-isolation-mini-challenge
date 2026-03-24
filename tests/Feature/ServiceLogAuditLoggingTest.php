<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\CrpAuditLog;
use App\Models\ServiceLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceLogAuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::set(null);

        parent::tearDown();
    }

    public function test_creating_service_log_writes_crp_audit_log_entry(): void
    {
        $crpId = (string) Str::uuid();
        TenantContext::set($crpId);

        $staff = User::factory()->create();

        $client = Client::create([
            'crp_id' => $crpId,
            'first_name' => 'Audited',
            'last_name' => 'Client',
            'ssn' => '999-99-9999',
            'dob' => '2000-01-01',
            'status' => 'active',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crpId,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'goal_id' => null,
            'notes_master' => ['narrative' => 'Initial visit'],
            'billing_status' => 'pending',
        ]);

        $this->assertDatabaseHas('crp_audit_logs', [
            'resource_type' => 'service_logs',
            'resource_id' => $log->id,
            'action_type' => 'created',
        ]);

        $audit = CrpAuditLog::where('resource_id', $log->id)->where('action_type', 'created')->first();
        $this->assertNotNull($audit);
        $this->assertNull($audit->old_values);
        $this->assertIsArray($audit->new_values);
        $this->assertSame('pending', $audit->new_values['billing_status']);
        $this->assertSame(64, strlen($audit->hash));
    }

    public function test_updating_service_log_writes_audit_entry_with_old_and_new_values(): void
    {
        $crpId = (string) Str::uuid();
        TenantContext::set($crpId);

        $staff = User::factory()->create();

        $client = Client::create([
            'crp_id' => $crpId,
            'first_name' => 'Audited',
            'last_name' => 'Client',
            'ssn' => '888-88-8888',
            'dob' => '2001-02-02',
            'status' => 'active',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crpId,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'goal_id' => null,
            'notes_master' => ['narrative' => 'Before'],
            'billing_status' => 'pending',
        ]);

        CrpAuditLog::query()->delete();

        $log->update([
            'notes_master' => ['narrative' => 'After update'],
        ]);

        $audit = CrpAuditLog::where('resource_id', $log->id)->where('action_type', 'updated')->first();

        $this->assertNotNull($audit);
        $this->assertSame(['narrative' => 'Before'], $audit->old_values['notes_master']);
        $this->assertSame(['narrative' => 'After update'], $audit->new_values['notes_master']);
    }
}
