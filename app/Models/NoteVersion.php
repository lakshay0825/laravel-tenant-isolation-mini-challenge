<?php

namespace App\Models;

use App\Models\Scopes\TenantScopeThroughServiceLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_log_id',
        'version_number',
        'data',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScopeThroughServiceLog);

        static::creating(function (NoteVersion $version) {
            $version->created_at ??= now();
        });
    }

    public function serviceLog(): BelongsTo
    {
        return $this->belongsTo(ServiceLog::class);
    }
}
