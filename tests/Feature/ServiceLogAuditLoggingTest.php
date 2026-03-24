<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ServiceLog;
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

    public function test_creating_service_log_writes_audit_entry(): void
    {
        $crpId = (string) Str::uuid();
        TenantContext::set($crpId);

        $client = Client::create([
            'crp_id' => $crpId,
            'name' => 'Audited Client',
            'ssn' => '999-99-9999',
            'dob' => '2000-01-01',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crpId,
            'client_id' => $client->id,
            'service_type' => 'intake',
            'notes' => 'Initial visit',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ServiceLog::class,
            'auditable_id' => $log->id,
            'event' => 'created',
        ]);

        $audit = AuditLog::where('auditable_id', $log->id)->where('event', 'created')->first();
        $this->assertNotNull($audit);
        $this->assertNull($audit->old_values);
        $this->assertIsArray($audit->new_values);
        $this->assertSame('intake', $audit->new_values['service_type']);
    }

    public function test_updating_service_log_writes_audit_entry_with_old_and_new_values(): void
    {
        $crpId = (string) Str::uuid();
        TenantContext::set($crpId);

        $client = Client::create([
            'crp_id' => $crpId,
            'name' => 'Audited Client',
            'ssn' => '888-88-8888',
            'dob' => '2001-02-02',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crpId,
            'client_id' => $client->id,
            'service_type' => 'follow_up',
            'notes' => 'Before',
        ]);

        AuditLog::query()->delete();

        $log->update(['notes' => 'After update']);

        $audit = AuditLog::where('auditable_id', $log->id)->where('event', 'updated')->first();

        $this->assertNotNull($audit);
        $this->assertSame('Before', $audit->old_values['notes']);
        $this->assertSame('After update', $audit->new_values['notes']);
    }
}
