<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'crp_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
