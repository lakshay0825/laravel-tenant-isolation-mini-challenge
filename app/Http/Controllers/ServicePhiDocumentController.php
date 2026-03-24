<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\Signature;
use App\Services\PhiDocumentStorageService;
use Illuminate\Http\Request;

class ServicePhiDocumentController extends Controller
{
    public function __construct(
        private PhiDocumentStorageService $phiStorage,
    ) {}

    public function show(Request $request, string $serviceLog)
    {
        abort_unless($request->hasValidSignature(), 403);

        $log = ServiceLog::withoutGlobalScopes()->findOrFail($serviceLog);

        $signature = Signature::withoutGlobalScopes()
            ->where('service_log_id', $log->id)
            ->where('crp_id', $log->crp_id)
            ->orderBy('id')
            ->first();

        if ($signature === null || $signature->s3_path === null || ! $this->phiStorage->exists($signature->s3_path)) {
            abort(404);
        }

        return $this->phiStorage->responseForRelativePath($signature->s3_path);
    }
}
