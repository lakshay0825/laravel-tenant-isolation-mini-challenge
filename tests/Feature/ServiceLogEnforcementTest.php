<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ServiceLog;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServiceLogEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        config([
            'compliance.enforce_duplicate_detection' => false,
            'compliance.enforce_staff_time_conflicts' => false,
        ]);
        TenantContext::set(null);
        parent::tearDown();
    }

    public function test_locked_service_log_cannot_be_updated(): void
    {
        config(['compliance.service_log_lock_days' => 10]);

        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '555-55-5555',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $log = ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => ['narrative' => 'locked soon'],
            'billing_status' => 'pending',
        ]);

        $log->forceFill(['created_at' => now()->subDays(11)])->saveQuietly();

        $this->expectException(ValidationException::class);

        $log->fresh()->update(['billing_status' => 'paid']);
    }

    public function test_duplicate_enforcement_blocks_second_identical_hash(): void
    {
        config(['compliance.enforce_duplicate_detection' => true]);

        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '666-66-6666',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $notes = ['narrative' => 'duplicate me'];

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => $notes,
            'billing_status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'notes_master' => $notes,
            'billing_status' => 'pending',
        ]);
    }

    public function test_time_conflict_enforcement_blocks_overlap(): void
    {
        config(['compliance.enforce_staff_time_conflicts' => true]);

        $crp = (string) Str::uuid();
        TenantContext::set($crp);

        $staff = User::factory()->create();
        $client = Client::create([
            'crp_id' => $crp,
            'first_name' => 'A',
            'last_name' => 'B',
            'ssn' => '777-77-7777',
            'dob' => '1990-01-01',
            'status' => 'active',
        ]);

        $start = now()->addDay()->startOfHour();
        $end = $start->copy()->addHour();

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'started_at' => $start,
            'ended_at' => $end,
            'notes_master' => ['narrative' => 'first slot'],
            'billing_status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);

        ServiceLog::create([
            'crp_id' => $crp,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'started_at' => $start->copy()->addMinutes(30),
            'ended_at' => $end->copy()->addMinutes(30),
            'notes_master' => ['narrative' => 'overlap slot'],
            'billing_status' => 'pending',
        ]);
    }
}
