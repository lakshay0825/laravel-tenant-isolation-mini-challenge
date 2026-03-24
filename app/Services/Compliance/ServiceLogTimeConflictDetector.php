<?php

namespace App\Services\Compliance;

use App\Models\ServiceLog;
use Illuminate\Database\Eloquent\Collection;

final class ServiceLogTimeConflictDetector
{
    /**
     * Overlapping intervals share at least one instant: start1 < end2 AND start2 < end1
     * (half-open intervals can be adjusted; we use inclusive overlap for scheduled blocks).
     *
     * @return Collection<int, ServiceLog>
     */
    public function findOverlappingForStaff(ServiceLog $candidate): Collection
    {
        if ($candidate->started_at === null || $candidate->ended_at === null) {
            return new Collection;
        }

        if ($candidate->ended_at->lessThanOrEqualTo($candidate->started_at)) {
            return new Collection;
        }

        $query = ServiceLog::query()
            ->where('staff_id', $candidate->staff_id)
            ->whereNotNull('started_at')
            ->whereNotNull('ended_at')
            ->whereColumn('ended_at', '>', 'started_at')
            ->where('started_at', '<', $candidate->ended_at)
            ->where('ended_at', '>', $candidate->started_at);

        if ($candidate->exists) {
            $query->where('id', '!=', $candidate->id);
        }

        return $query->orderBy('started_at')->get();
    }

    public function hasConflict(ServiceLog $candidate): bool
    {
        return $this->findOverlappingForStaff($candidate)->isNotEmpty();
    }
}
