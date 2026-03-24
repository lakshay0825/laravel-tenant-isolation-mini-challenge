<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationAndPhiEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::set(null);

        parent::tearDown();
    }

    public function test_tenant_b_cannot_access_tenant_a_client_via_eloquent(): void
    {
        $tenantA = (string) Str::uuid();
        $tenantB = (string) Str::uuid();

        TenantContext::set($tenantA);
        $client = Client::create([
            'crp_id' => $tenantA,
            'name' => 'Tenant A Client',
            'ssn' => '000-00-0001',
            'dob' => '1990-01-15',
        ]);

        TenantContext::set($tenantB);
        $this->assertNull(Client::find($client->id));

        TenantContext::set($tenantA);
        $this->assertNotNull(Client::find($client->id));
    }

    public function test_raw_database_row_does_not_contain_plaintext_ssn(): void
    {
        $tenantId = (string) Str::uuid();
        $plainSsn = '123-45-6789';

        TenantContext::set($tenantId);
        $client = Client::create([
            'crp_id' => $tenantId,
            'name' => 'Vault Test',
            'ssn' => $plainSsn,
            'dob' => '1975-06-30',
        ]);

        $row = DB::table('clients')->where('id', $client->id)->first();

        $this->assertNotNull($row);
        $this->assertStringNotContainsString($plainSsn, $row->ssn);
        $this->assertSame($plainSsn, Client::find($client->id)->ssn);
    }
}
