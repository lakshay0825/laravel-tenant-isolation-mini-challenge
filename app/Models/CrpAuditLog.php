<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class CrpAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'crp_audit_logs';

    protected $fillable = [
        'request_id',
        'crp_id',
        'actor_id',
        'action_type',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'outcome',
        'action_context',
        'hash',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'encrypted:array',
            'new_values' => 'encrypted:array',
            'action_context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (CrpAuditLog $log) {
            $log->created_at ??= now();
        });
    }
}
