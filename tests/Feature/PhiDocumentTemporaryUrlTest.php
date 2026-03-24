<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ServiceLog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhiDocumentTemporaryUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::set(null);

        parent::tearDown();
    }

    public function test_signed_temporary_url_returns_uploaded_document(): void
    {
        Storage::fake('phi_local');

        $crpId = (string) Str::uuid();
        TenantContext::set($crpId);

        $client = Client::create([
            'crp_id' => $crpId,
            'name' => 'Doc Client',
            'ssn' => '777-77-7777',
            'dob' => '1999-12-31',
        ]);

        $relativePath = "signatures/{$client->id}.txt";
        Storage::disk('phi_local')->put($relativePath, 'simulated-signature-bytes');

        $log = ServiceLog::create([
            'crp_id' => $crpId,
            'client_id' => $client->id,
            'service_type' => 'intake',
            'notes' => 'With attachment',
            'document_path' => $relativePath,
        ]);

        $url = URL::temporarySignedRoute(
            'phi.service-log-document',
            now()->addMinutes(10),
            ['serviceLog' => $log->id],
        );

        $response = $this->get($url);

        $response->assertOk();
        $body = $response->streamedContent();
        if ($body === false) {
            $body = $response->getContent();
        }
        $this->assertStringContainsString('simulated-signature-bytes', $body);
    }
}
