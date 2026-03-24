<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceLog extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'crp_id',
        'client_id',
        'service_type',
        'notes',
        'document_path',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
