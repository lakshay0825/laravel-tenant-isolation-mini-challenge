<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServicePhiDocumentController extends Controller
{
    public function show(Request $request, string $serviceLog): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $log = ServiceLog::withoutGlobalScopes()->findOrFail($serviceLog);

        $signature = Signature::withoutGlobalScopes()
            ->where('service_log_id', $log->id)
            ->where('crp_id', $log->crp_id)
            ->orderBy('id')
            ->first();

        if ($signature === null || $signature->s3_path === null || ! Storage::disk('phi_local')->exists($signature->s3_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('phi_local')->path($signature->s3_path));
    }
}
