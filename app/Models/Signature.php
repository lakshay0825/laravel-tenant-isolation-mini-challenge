<?php

namespace App\Models;

use App\Models\Scopes\TenantScopeThroughServiceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_log_id',
        'crp_id',
        'type',
        's3_path',
        'signed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScopeThroughServiceLog);

        static::creating(function (Signature $signature) {
            $signature->created_at ??= now();
        });
    }

    public function serviceLog(): BelongsTo
    {
        return $this->belongsTo(ServiceLog::class);
    }
}
