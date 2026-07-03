<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DevelopersGuideController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\Sppt\AkaunController;
use App\Http\Controllers\Api\Sppt\JaminanController;
use App\Http\Controllers\Api\Sppt\KutipanController;
use App\Http\Controllers\Api\Sppt\PengeluaranDanaController;
use App\Http\Controllers\Api\Sppt\PermohonanController;
use App\Http\Controllers\Api\Sppt\SpptDashboardController;
use App\Http\Controllers\Api\Sppt\SpptDatasetController;
use App\Http\Controllers\Api\Sppt\SpptReferenceDataController;
use App\Http\Controllers\Api\Sppt\SpptSetupController;
use App\Http\Controllers\Api\Sppt\UsahawanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth)
Route::prefix('public')->group(function () {
    Route::get('/site', [PublicController::class, 'site']);
    Route::get('/pages/frontpage', [PublicController::class, 'frontpage']);
    Route::get('/pages/{slug}', [PublicController::class, 'pageBySlug']);
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/password', [AuthController::class, 'changePassword']);
        Route::post('/avatar', [AuthController::class, 'uploadAvatar']);
        Route::delete('/avatar', [AuthController::class, 'removeAvatar']);
    });
});

// Settings GET is public (used by SPA before auth)
Route::get('/settings', [SettingController::class, 'index']);

// Protected admin routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('pages', PageController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);

    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::put('/media/{media}', [MediaController::class, 'update']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    Route::put('/settings', [SettingController::class, 'update']);
    Route::get('/settings/admin-menu-prefs', [SettingController::class, 'adminMenuPrefs']);
    Route::put('/settings/admin-menu-prefs', [SettingController::class, 'updateAdminMenuPrefs']);
    Route::get('/settings/storefront-menu', [SettingController::class, 'storefrontMenu']);
    Route::put('/settings/storefront-menu', [SettingController::class, 'updateStorefrontMenu']);

    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    Route::get('/developers-guide', [DevelopersGuideController::class, 'show']);
    Route::put('/developers-guide', [DevelopersGuideController::class, 'update']);

    // SPPT — Pengurusan Pembiayaan
    Route::prefix('sppt')->middleware('permission:sppt.view')->group(function () {
        Route::get('/dashboard/summary', [SpptDashboardController::class, 'summary']);
        Route::get('/reference-data', [SpptReferenceDataController::class, 'index']);
        Route::get('/setup', [SpptSetupController::class, 'index']);
        Route::get('/setup/{key}', [SpptSetupController::class, 'show']);
        Route::put('/setup/{key}', [SpptSetupController::class, 'update'])->middleware('permission:sppt.edit');
        Route::get('/datasets/{module}/{key}', [SpptDatasetController::class, 'show']);
        Route::get('/datasets/{module}', [SpptDatasetController::class, 'module']);

        Route::get('/permohonan/summary', [PermohonanController::class, 'summary']);
        Route::apiResource('permohonan', PermohonanController::class)->middleware([
            'store' => 'permission:sppt.create',
            'update' => 'permission:sppt.edit',
            'destroy' => 'permission:sppt.delete',
        ]);

        Route::get('/usahawan/summary', [UsahawanController::class, 'summary']);
        Route::apiResource('usahawan', UsahawanController::class)->middleware([
            'store' => 'permission:sppt.create',
            'update' => 'permission:sppt.edit',
            'destroy' => 'permission:sppt.delete',
        ]);

        Route::get('/akaun/summary', [AkaunController::class, 'summary']);
        Route::get('/akaun', [AkaunController::class, 'index']);
        Route::get('/akaun/{id}', [AkaunController::class, 'show']);

        Route::get('/pengeluaran-dana', [PengeluaranDanaController::class, 'index']);
        Route::get('/jaminan', [JaminanController::class, 'index']);
        Route::get('/kutipan', [KutipanController::class, 'index']);
    });
});
