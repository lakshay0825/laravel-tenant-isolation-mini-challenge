<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\ServiceLog;
use App\Models\User;
use App\Services\Compliance\ServiceLogDuplicateDetector;
use App\Services\Compliance\ServiceLogLockService;
use App\Services\Compliance\ServiceLogTimeConflictDetector;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceLogComplianceServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::set(null);
        parent::tearDown();
    }

    public function test_duplicate_detector_finds_same_narrative_hash_for_client(): void
    {
        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '111-11-1111',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $notes = ['narrative' => 'Same text'];
        $hash = hash('sha256', json_encode($notes, JSON_THROW_ON_ERROR));

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => $notes,
            'narrative_hash' => $hash,
            'billing_status' => 'pending',
        ]);

        $second = new ServiceLog([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => $notes,
            'billing_status' => 'pending',
        ]);
        $second->narrative_hash = $hash;

        $detector = app(ServiceLogDuplicateDetector::class);

        $this->assertTrue($detector->hasPotentialDuplicate($second));
    }

    public function test_time_conflict_detector_finds_overlap(): void
    {
        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '222-22-2222',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $start = now()->startOfHour();
        $end = $start->copy()->addHour();

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'started_at' => $start,
            'ended_at' => $end,
            'notes_master' => ['narrative' => 'first'],
            'billing_status' => 'pending',
        ]);

        $candidate = new ServiceLog([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'started_at' => $start->copy()->addMinutes(30),
            'ended_at' => $end->copy()->addMinutes(30),
            'notes_master' => ['narrative' => 'overlap'],
            'billing_status' => 'pending',
        ]);

        $this->assertTrue(app(ServiceLogTimeConflictDetector::class)->hasConflict($candidate));
    }

    public function test_lock_service_reports_locked_after_retention_window(): void
    {
        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '333-33-3333',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => ['narrative' => 'old'],
            'billing_status' => 'pending',
        ]);

        $log->forceFill(['created_at' => now()->subDays(11)])->saveQuietly();

        $this->assertTrue(app(ServiceLogLockService::class)->isLocked($log->fresh()));
    }

    public function test_apply_locks_command_stamps_locked_at(): void
    {
        config(['compliance.service_log_lock_days' => 10]);

        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '444-44-4444',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => ['narrative' => 'stale'],
            'billing_status' => 'pending',
        ]);

        $log->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $this->artisan('service-logs:apply-locks')->assertSuccessful();

        $this->assertNotNull($log->fresh()->locked_at);
    }
}
