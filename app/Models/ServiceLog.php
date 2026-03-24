<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Services\Compliance\ServiceLogLockService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceLog extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'crp_id',
        'client_id',
        'staff_id',
        'goal_id',
        'started_at',
        'ended_at',
        'notes_master',
        'narrative_hash',
        'billing_status',
        'invoice_number',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'notes_master' => 'encrypted:array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function isLocked(): bool
    {
        return app(ServiceLogLockService::class)->isLocked($this);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function noteVersions(): HasMany
    {
        return $this->hasMany(NoteVersion::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }
}
