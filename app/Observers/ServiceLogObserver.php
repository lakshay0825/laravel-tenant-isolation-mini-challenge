<?php

namespace App\Observers;

use App\Models\CrpAuditLog;
use App\Models\ServiceLog;
use Illuminate\Support\Str;

class ServiceLogObserver
{
    /** @var array<string, array<string, mixed>> */
    private array $originalBeforeUpdate = [];

    public function saving(ServiceLog $serviceLog): void
    {
        if (! $serviceLog->exists) {
            return;
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

    public function created(ServiceLog $serviceLog): void
    {
        $newValues = $this->snapshot($serviceLog);

        CrpAuditLog::create([
            'request_id' => request()?->header('X-Request-Id') ?? (string) Str::uuid(),
            'crp_id' => $serviceLog->crp_id,
            'actor_id' => auth()->id(),
            'action_type' => 'created',
            'resource_type' => 'service_logs',
            'resource_id' => $serviceLog->getKey(),
            'old_values' => null,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'outcome' => 'success',
            'action_context' => null,
            'hash' => $this->integrityHash('created', $serviceLog->getKey(), null, $newValues),
        ]);
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

        CrpAuditLog::create([
            'request_id' => request()?->header('X-Request-Id') ?? (string) Str::uuid(),
            'crp_id' => $serviceLog->crp_id,
            'actor_id' => auth()->id(),
            'action_type' => 'updated',
            'resource_type' => 'service_logs',
            'resource_id' => $serviceLog->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'outcome' => 'success',
            'action_context' => null,
            'hash' => $this->integrityHash('updated', $serviceLog->getKey(), $oldValues, $newValues),
        ]);
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
