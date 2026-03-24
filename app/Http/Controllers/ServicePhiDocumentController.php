<?php

namespace App\Http\Controllers;

use App\Models\ServiceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServicePhiDocumentController extends Controller
{
    public function show(Request $request, string $serviceLog): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $log = ServiceLog::withoutGlobalScopes()->findOrFail($serviceLog);

        if ($log->document_path === null || ! Storage::disk('phi_local')->exists($log->document_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('phi_local')->path($log->document_path));
    }
}
