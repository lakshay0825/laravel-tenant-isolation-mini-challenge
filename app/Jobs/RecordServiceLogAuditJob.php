<?php

namespace App\Jobs;

use App\Models\CrpAuditLog;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordServiceLogAuditJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $crpId,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $previous = TenantContext::crpId();
        TenantContext::set($this->crpId);

        try {
            CrpAuditLog::create($this->payload);
        } finally {
            TenantContext::set($previous);
        }
    }
}
