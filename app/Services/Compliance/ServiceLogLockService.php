<?php

namespace App\Services\Compliance;

use App\Models\ServiceLog;
use Carbon\CarbonInterface;

final class ServiceLogLockService
{
    public function lockDays(): int
    {
        return max(1, (int) config('compliance.service_log_lock_days', 10));
    }

    /**
     * Locked if locked_at is set, or the log is older than the configured lock window.
     */
    public function isLocked(ServiceLog $log): bool
    {
        if ($log->locked_at !== null) {
            return true;
        }

        $created = $log->created_at;
        if (! $created instanceof CarbonInterface) {
            return false;
        }

        return $created->copy()->addDays($this->lockDays())->isPast();
    }

    /**
     * Stamp locked_at for logs past the lock window (idempotent for already-stamped rows).
     */
    public function applyLocksForExpiredLogs(): int
    {
        $cutoff = now()->subDays($this->lockDays());
        $now = now();

        return ServiceLog::withoutGlobalScopes()
            ->whereNull('locked_at')
            ->where('created_at', '<=', $cutoff)
            ->update(['locked_at' => $now]);
    }
}
