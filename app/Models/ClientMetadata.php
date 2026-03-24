<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMetadata extends Model
{
    protected $table = 'client_metadata';

    protected $fillable = [
        'client_id',
        'crp_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
