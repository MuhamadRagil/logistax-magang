<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\InternController;
use App\Http\Controllers\Api\OfficeLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/intern/register', [AuthController::class, 'internRegister']);
    Route::post('/intern/login', [AuthController::class, 'internLogin']);

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum', 'admin.role:admin_magang,spv_mentor'])->group(function () {
    Route::get('/interns', [InternController::class, 'index']);
    Route::get('/interns/{intern}', [InternController::class, 'show']);

    Route::middleware('admin.role:admin_magang')->group(function () {
        Route::post('/interns', [InternController::class, 'store']);
        Route::patch('/interns/{intern}', [InternController::class, 'update']);
        Route::post('/interns/{intern}/approve', [InternController::class, 'approve']);
        Route::post('/interns/{intern}/reject', [InternController::class, 'reject']);
        Route::post('/interns/{intern}/extend', [InternController::class, 'extend']);
        Route::post('/interns/{intern}/mark-failed', [InternController::class, 'markFailed']);
        Route::post('/interns/{intern}/mark-completed', [InternController::class, 'markCompleted']);

        Route::apiResource('divisions', DivisionController::class);
        Route::apiResource('office-locations', OfficeLocationController::class);
    });

    // admin_magang & spv_mentor keduanya boleh akses (spv_mentor discope di controller)
    Route::get('/attendance/monthly-report', [AttendanceController::class, 'monthlyReport']);
    Route::get('/attendance/pending-approvals', [AttendanceController::class, 'pendingApprovals']);
    Route::post('/attendance/{attendance}/approve', [AttendanceController::class, 'approve']);
    Route::post('/attendance/{attendance}/reject', [AttendanceController::class, 'reject']);

    // admin_magang & spv_mentor keduanya boleh akses (hak & scope lebih rinci di controller)
    Route::get('/evaluations/pending', [EvaluationController::class, 'pending']);
    Route::post('/evaluations', [EvaluationController::class, 'store']);
    Route::patch('/evaluations/{evaluation}', [EvaluationController::class, 'update']);
    Route::get('/evaluations/intern/{internId}', [EvaluationController::class, 'showForIntern']);

    // admin_magang & spv_mentor keduanya boleh akses (spv_mentor discope di controller)
    Route::get('/certificates/{internId}', [CertificateController::class, 'show']);
    Route::get('/certificates/{internId}/download', [CertificateController::class, 'download']);

    Route::middleware('admin.role:admin_magang')->group(function () {
        Route::post('/certificates/generate/{internId}', [CertificateController::class, 'generate']);
        Route::post('/certificates/regenerate/{internId}', [CertificateController::class, 'regenerate']);
        Route::get('/certificates/preview/{internId}', [CertificateController::class, 'preview']);
    });
});

Route::middleware(['auth:sanctum', 'intern.auth'])->prefix('attendance')->group(function () {
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::post('/leave-request', [AttendanceController::class, 'leaveRequest']);
    Route::get('/my-history', [AttendanceController::class, 'myHistory']);
});

Route::middleware(['auth:sanctum', 'intern.auth'])->group(function () {
    Route::get('/my-evaluation', [EvaluationController::class, 'myEvaluation']);
    Route::get('/my-certificate', [CertificateController::class, 'myCertificate']);
    Route::get('/my-certificate/download', [CertificateController::class, 'downloadMyCertificate']);
});
