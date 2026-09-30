<?php

use App\Http\Controllers\AuditPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/audits/{audit}/pdf', [AuditPdfController::class, 'download'])
        ->name('admin.audits.pdf');
    Route::get('/admin/audits/{audit}/pdf/stream', [AuditPdfController::class, 'stream'])
        ->name('admin.audits.pdf.stream');
});
