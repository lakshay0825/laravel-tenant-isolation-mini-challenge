<?php

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $crpId = TenantContext::crpId();

        if ($crpId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->getTable().'.crp_id', $crpId);
    }
}
