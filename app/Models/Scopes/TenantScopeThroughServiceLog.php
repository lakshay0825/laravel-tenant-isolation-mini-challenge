<?php

namespace App\Models\Scopes;

use App\Models\ServiceLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScopeThroughServiceLog implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $crpId = TenantContext::crpId();

        if ($crpId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $serviceLogs = (new ServiceLog)->getTable();

        $builder->whereExists(function ($query) use ($serviceLogs, $model, $crpId) {
            $query->selectRaw('1')
                ->from($serviceLogs)
                ->whereColumn("{$serviceLogs}.id", $model->getTable().'.service_log_id')
                ->where("{$serviceLogs}.crp_id", $crpId);
        });
    }
}
