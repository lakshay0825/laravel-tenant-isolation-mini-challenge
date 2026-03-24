<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    protected $fillable = [
        'state_code',
        'form_code',
        'version',
        'schema',
        'mapping',
        'pdf_template_path',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'mapping' => 'array',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class, 'template_id');
    }
}
