<?php

use App\Http\Controllers\ServicePhiDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/phi/service-logs/{serviceLog}/document', [ServicePhiDocumentController::class, 'show'])
    ->name('phi.service-log-document')
    ->middleware('signed');
