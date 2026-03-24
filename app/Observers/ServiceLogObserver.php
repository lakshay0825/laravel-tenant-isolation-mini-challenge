<?php

namespace App\Observers;

use App\Jobs\RecordServiceLogAuditJob;
use App\Models\ServiceLog;
use App\Services\Compliance\ServiceLogDuplicateDetector;
use App\Services\Compliance\ServiceLogLockService;
use App\Services\Compliance\ServiceLogTimeConflictDetector;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceLogObserver
{
    /** @var array<string, array<string, mixed>> */
    private array $originalBeforeUpdate = [];

    public function __construct(
        private ServiceLogLockService $lockService,
        private ServiceLogDuplicateDetector $duplicateDetector,
        private ServiceLogTimeConflictDetector $timeConflictDetector,
    ) {}

    public function saving(ServiceLog $serviceLog): void
    {
        if ($serviceLog->isDirty('notes_master') || ! $serviceLog->exists) {
            if (is_array($serviceLog->notes_master)) {
                $serviceLog->narrative_hash = hash('sha256', json_encode($serviceLog->notes_master, JSON_THROW_ON_ERROR));
            }
        }

        if ($serviceLog->exists) {
            if ($this->lockService->isLocked($serviceLog)) {
                throw ValidationException::withMessages([
                    'service_log' => __('This service log is locked and cannot be modified.'),
                ]);
            }

            $dirty = $serviceLog->getDirty();
            unset($dirty['updated_at']);

            if ($dirty === []) {
                return;
            }

            $id = $serviceLog->getKey();
            foreach (array_keys($dirty) as $key) {
                $this->originalBeforeUpdate[$id][$key] = $serviceLog->getOriginal($key);
            }
        }

        if (config('compliance.enforce_duplicate_detection') && $serviceLog->narrative_hash) {
            if ($this->duplicateDetector->hasPotentialDuplicate($serviceLog)) {
                throw ValidationException::withMessages([
                    'service_log' => __('A potential duplicate service log exists for this client and narrative hash.'),
                ]);
            }
        }

        if (config('compliance.enforce_staff_time_conflicts') && $serviceLog->started_at && $serviceLog->ended_at) {
            if ($this->timeConflictDetector->hasConflict($serviceLog)) {
                throw ValidationException::withMessages([
                    'service_log' => __('This service log overlaps another appointment for the same staff member.'),
                ]);
            }
        }
    }

    public function created(ServiceLog $serviceLog): void
    {
        $newValues = $this->snapshot($serviceLog);

        RecordServiceLogAuditJob::dispatch($serviceLog->crp_id, $this->auditPayload(
            $serviceLog,
            'created',
            null,
            $newValues,
        ));
    }

    public function updated(ServiceLog $serviceLog): void
    {
        $changes = $serviceLog->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $id = $serviceLog->getKey();
        $prior = $this->originalBeforeUpdate[$id] ?? [];
        unset($this->originalBeforeUpdate[$id]);

        $newValues = [];
        foreach (array_keys($changes) as $key) {
            $newValues[$key] = $this->normalizeAuditValue($serviceLog->getAttribute($key));
        }

        $oldValues = [];
        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $this->normalizeAuditValue($prior[$key] ?? null);
        }

        RecordServiceLogAuditJob::dispatch($serviceLog->crp_id, $this->auditPayload(
            $serviceLog,
            'updated',
            $oldValues === [] ? null : $oldValues,
            $newValues,
        ));
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    private function auditPayload(ServiceLog $serviceLog, string $action, ?array $old, array $new): array
    {
        $resourceId = $serviceLog->getKey();

        return [
            'request_id' => request()?->header('X-Request-Id') ?? (string) Str::uuid(),
            'crp_id' => $serviceLog->crp_id,
            'actor_id' => auth()->id(),
            'action_type' => $action,
            'resource_type' => 'service_logs',
            'resource_id' => $resourceId,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'outcome' => 'success',
            'action_context' => null,
            'hash' => $this->integrityHash($action, $resourceId, $old, $new),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ServiceLog $serviceLog): array
    {
        return [
            'id' => $serviceLog->id,
            'crp_id' => $serviceLog->crp_id,
            'client_id' => $serviceLog->client_id,
            'staff_id' => $serviceLog->staff_id,
            'goal_id' => $serviceLog->goal_id,
            'started_at' => $this->normalizeAuditValue($serviceLog->started_at),
            'ended_at' => $this->normalizeAuditValue($serviceLog->ended_at),
            'notes_master' => $serviceLog->notes_master,
            'narrative_hash' => $serviceLog->narrative_hash,
            'billing_status' => $serviceLog->billing_status,
            'invoice_number' => $serviceLog->invoice_number,
            'locked_at' => $this->normalizeAuditValue($serviceLog->locked_at),
        ];
    }

    private function normalizeAuditValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function integrityHash(string $action, string $resourceId, ?array $old, ?array $new): string
    {
        return hash('sha256', json_encode([
            'action_type' => $action,
            'resource_type' => 'service_logs',
            'resource_id' => $resourceId,
            'old_values' => $old,
            'new_values' => $new,
        ], JSON_THROW_ON_ERROR));
    }
}
