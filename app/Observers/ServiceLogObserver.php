<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ServiceLog;

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

        $this->originalBeforeUpdate[$serviceLog->getKey()] = array_intersect_key(
            $serviceLog->getRawOriginal(),
            $dirty
        );
    }

    public function created(ServiceLog $serviceLog): void
    {
        AuditLog::create([
            'crp_id' => $serviceLog->crp_id,
            'auditable_type' => ServiceLog::class,
            'auditable_id' => $serviceLog->getKey(),
            'event' => 'created',
            'old_values' => null,
            'new_values' => $this->snapshot($serviceLog),
        ]);
    }

    public function updated(ServiceLog $serviceLog): void
    {
        $changes = $serviceLog->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $key = $serviceLog->getKey();
        $original = $this->originalBeforeUpdate[$key] ?? [];
        unset($this->originalBeforeUpdate[$key]);

        $old = array_intersect_key($original, $changes);

        AuditLog::create([
            'crp_id' => $serviceLog->crp_id,
            'auditable_type' => ServiceLog::class,
            'auditable_id' => $serviceLog->getKey(),
            'event' => 'updated',
            'old_values' => $old === [] ? null : $old,
            'new_values' => $changes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ServiceLog $serviceLog): array
    {
        $attributes = $serviceLog->getAttributes();
        unset($attributes['updated_at'], $attributes['created_at']);

        return $attributes;
    }
}
