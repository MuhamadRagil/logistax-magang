<?php

use App\Http\Controllers\Web\AttendanceController;
use App\Http\Controllers\Web\AuthenticatedSessionController;
use App\Http\Controllers\Web\CertificateController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\EvaluationController;
use App\Http\Controllers\Web\InternController;
use App\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth('web')->check() ? 'dashboard' : 'login');
});

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('logout');

Route::middleware(['auth:web', 'admin.role.web:admin_magang,spv_mentor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Interns — index/show/detail dibuka untuk admin_magang & spv_mentor
    // (di-scope ke mentee sendiri untuk spv_mentor), sama seperti API.
    Route::get('/interns', [InternController::class, 'index'])->name('interns.index');
    Route::get('/interns/{intern}/detail', [InternController::class, 'detail'])->name('interns.detail');

    Route::get('/evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::patch('/evaluations/{evaluation}', [EvaluationController::class, 'update'])->name('evaluations.update');

    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/{attendance}/approve', [AttendanceController::class, 'approve'])->name('attendance.approve');
    Route::post('/attendance/{attendance}/reject', [AttendanceController::class, 'reject'])->name('attendance.reject');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{intern}/download', [CertificateController::class, 'download'])->name('certificates.download');

    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/attendance/export', [ReportController::class, 'export'])->name('attendance.export');

    // admin_magang only — mirrors routes/api.php's inner admin.role:admin_magang groups.
    Route::middleware('admin.role.web:admin_magang')->group(function () {
        Route::post('/interns', [InternController::class, 'store'])->name('interns.store');
        Route::post('/interns/{intern}/approve', [InternController::class, 'approve'])->name('interns.approve');
        Route::post('/interns/{intern}/reject', [InternController::class, 'reject'])->name('interns.reject');

        Route::post('/certificates/{intern}/generate', [CertificateController::class, 'generate'])->name('certificates.generate');
        Route::post('/certificates/{intern}/regenerate', [CertificateController::class, 'regenerate'])->name('certificates.regenerate');
        Route::get('/certificates/{intern}/preview', [CertificateController::class, 'preview'])->name('certificates.preview');
    });
});
