<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmission extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'crp_id',
        'client_id',
        'template_id',
        'form_data',
        'pdf_s3_key',
        'submitted_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'form_data' => 'encrypted:array',
            'submitted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (FormSubmission $row) {
            $row->created_at ??= now();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'template_id');
    }
}
