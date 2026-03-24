<?php

namespace App\Services\Compliance;

use App\Models\ServiceLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class ServiceLogDuplicateDetector
{
    /**
     * Other service logs for the same client with the same narrative_hash in the lookback window.
     *
     * @return Collection<int, ServiceLog>
     */
    public function findPotentialDuplicates(ServiceLog $log): Collection
    {
        $hash = $log->narrative_hash;
        if ($hash === null || $hash === '') {
            return new Collection;
        }

        $hours = max(1, (int) config('compliance.duplicate_lookback_hours', 72));
        $since = Carbon::now()->subHours($hours);

        return ServiceLog::query()
            ->where('client_id', $log->client_id)
            ->where('narrative_hash', $hash)
            ->when($log->getKey() !== null, fn ($q) => $q->where('id', '!=', $log->getKey()))
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();
    }

    public function hasPotentialDuplicate(ServiceLog $log): bool
    {
        return $this->findPotentialDuplicates($log)->isNotEmpty();
    }
}
