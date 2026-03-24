<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'crp_id',
        'name',
        'ssn',
        'dob',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'ssn' => 'encrypted',
            'dob' => 'encrypted:date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class);
    }
}
