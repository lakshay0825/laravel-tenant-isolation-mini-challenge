<?php

namespace App\Console\Commands;

use App\Services\Compliance\ServiceLogLockService;
use Illuminate\Console\Command;

class ApplyServiceLogLocksCommand extends Command
{
    protected $signature = 'service-logs:apply-locks';

    protected $description = 'Set locked_at on service logs past the configured retention lock window';

    public function handle(ServiceLogLockService $locks): int
    {
        $count = $locks->applyLocksForExpiredLogs();
        $this->info("Stamped locked_at on {$count} service log(s).");

        return self::SUCCESS;
    }
}
