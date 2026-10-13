<?php

use App\Enums\Permission;
use App\Http\Controllers\Api\AiProviderController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DevelopersGuideController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PemohonChatController;
use App\Http\Controllers\Api\PemohonPermohonanController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PublicController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\Sppt\CawanganController;
use App\Http\Controllers\Api\Sppt\AiCreditScoringController;
use App\Http\Controllers\Api\Sppt\AiRiskScoringController;
use App\Http\Controllers\Api\Sppt\AkaunController;
use App\Http\Controllers\Api\Sppt\BayaranController;
use App\Http\Controllers\Api\Sppt\HardRuleCheckController;
use App\Http\Controllers\Api\Sppt\JadualAnsuranController;
use App\Http\Controllers\Api\Sppt\JaminanController;
use App\Http\Controllers\Api\Sppt\PembiayaanFormulaController;
use App\Http\Controllers\Api\Sppt\PenjadualanController;
use App\Http\Controllers\Api\Sppt\TawidhController;
use App\Http\Controllers\Api\Sppt\KoleksiController;
use App\Http\Controllers\Api\Sppt\KutipanController;
use App\Http\Controllers\Api\Sppt\PengeluaranDanaController;
use App\Http\Controllers\Api\Sppt\PermohonanController;
use App\Http\Controllers\Api\Sppt\SpptDashboardController;
use App\Http\Controllers\Api\Sppt\SpptDatasetController;
use App\Http\Controllers\Api\Sppt\SpptReferenceDataController;
use App\Http\Controllers\Api\Sppt\PenilaianPantasController;
use App\Http\Controllers\Api\Sppt\SpptSetupController;
use App\Http\Controllers\Api\Sppt\UsahawanController;
use App\Http\Controllers\Api\UserChatController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowConfigurationController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth)
Route::prefix('public')->group(function () {
    Route::get('/site', [PublicController::class, 'site']);
    Route::get('/pages/frontpage', [PublicController::class, 'frontpage']);
    Route::get('/pages/{slug}', [PublicController::class, 'pageBySlug']);

    Route::post('/otp/request', [OtpController::class, 'request'])->middleware('throttle:otp');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:otp');

    Route::prefix('sppt')->group(function () {
        Route::get('/hard-rules', [HardRuleCheckController::class, 'show']);
        Route::post('/hard-rules/check', [HardRuleCheckController::class, 'check'])->middleware('throttle:60,1');
        Route::post('/risk-scoring/score', [AiRiskScoringController::class, 'score'])->middleware('throttle:30,1');
    });

    Route::post('/pemohon/chat', [PemohonChatController::class, 'send'])->middleware('throttle:pemohon-chat');

    Route::prefix('pemohon')->middleware('throttle:pemohon-permohonan')->group(function () {
        Route::post('/permohonan', [PemohonPermohonanController::class, 'store']);
        Route::get('/permohonan/{permohonan}', [PemohonPermohonanController::class, 'show']);
        Route::put('/permohonan/{permohonan}', [PemohonPermohonanController::class, 'update']);
        Route::post('/permohonan/{permohonan}/dokumen', [PemohonPermohonanController::class, 'uploadDocument']);
        Route::patch('/permohonan/{permohonan}/dokumen/{attachmentId}', [PemohonPermohonanController::class, 'updateDocumentClass']);
        Route::delete('/permohonan/{permohonan}/dokumen/{attachmentId}', [PemohonPermohonanController::class, 'deleteDocument']);
        Route::post('/permohonan/dokumen/classify', [PemohonPermohonanController::class, 'classifyDocument']);
        Route::post('/permohonan/dokumen/verify', [PemohonPermohonanController::class, 'verifyDocument']);
        Route::post('/permohonan/{permohonan}/penilaian-pantas', [PemohonPermohonanController::class, 'penilaianPantas']);
    });
});

if (app()->environment('local')) {
    Route::get('/dev/php-limits', function () {
        return response()->json([
            'data' => [
                'uploadMaxFilesize' => ini_get('upload_max_filesize'),
                'postMaxSize' => ini_get('post_max_size'),
                'permohonanDocumentMaxKb' => config('sppt.permohonan_document_max_kb'),
            ],
        ]);
    });
}

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
    Route::get('/users/cawangan-options', [UserController::class, 'cawanganOptions']);
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

    // Integrasi AI — Penyedia AI (Anthropic Claude)
    Route::get('/platform/ai/providers', [AiProviderController::class, 'show'])->middleware('permission:settings.view');
    Route::put('/platform/ai/providers', [AiProviderController::class, 'update'])->middleware('permission:settings.edit');
    Route::post('/platform/ai/providers/test', [AiProviderController::class, 'test'])
        ->middleware(['permission:settings.edit', 'throttle:10,1']);

    // SPPT — Pengurusan Pembiayaan
    Route::prefix('sppt')->middleware('permission:sppt.view')->group(function () {
        Route::get('/dashboard/summary', [SpptDashboardController::class, 'summary']);
        Route::get('/reference-data', [SpptReferenceDataController::class, 'index']);
        Route::get('/setup', [SpptSetupController::class, 'index']);
        Route::get('/setup/{key}', [SpptSetupController::class, 'show']);
        Route::put('/setup/{key}', [SpptSetupController::class, 'update'])->middleware('permission:sppt.edit');

        Route::get('/cawangan/negeri-options', [CawanganController::class, 'negeriOptions']);
        Route::apiResource('cawangan', CawanganController::class)
            ->middlewareFor('store', 'permission:sppt.create')
            ->middlewareFor('update', 'permission:sppt.edit')
            ->middlewareFor('destroy', 'permission:sppt.delete');
        Route::get('/datasets/{module}/{key}', [SpptDatasetController::class, 'show']);
        Route::get('/datasets/{module}', [SpptDatasetController::class, 'module']);

        // POC 1 — Enjin Penilaian Pantas (C1–C4)
        Route::prefix('penilaian-pantas')->group(function () {
            Route::get('/', [PenilaianPantasController::class, 'index']);
            Route::get('/keadilan', [PenilaianPantasController::class, 'fairness']);
            Route::get('/permohonan/{permohonan}', [PenilaianPantasController::class, 'show']);
            Route::get('/permohonan/{permohonan}/semakan-pengeluaran', [PenilaianPantasController::class, 'disbursementCheck']);
            Route::get('/run/{run}', [PenilaianPantasController::class, 'run']);
            Route::middleware('permission:sppt.edit')->group(function () {
                Route::post('/permohonan/{permohonan}/mula', [PenilaianPantasController::class, 'start']);
                Route::post('/run/{run}/langkah/{kod}', [PenilaianPantasController::class, 'step']);
                Route::put('/permohonan/{permohonan}/data-alternatif', [PenilaianPantasController::class, 'updateDataAlternatif']);
                Route::middleware('throttle:30,1')->group(function () {
                    Route::post('/permohonan/{permohonan}/wajah', [PenilaianPantasController::class, 'verifyFace']);
                    Route::post('/permohonan/{permohonan}/data-alternatif/ekstrak', [PenilaianPantasController::class, 'extractDataAlternatif']);
                    Route::post('/permohonan/{permohonan}/dokumen-jualan', [PenilaianPantasController::class, 'analyzeSalesDocument']);
                });
            });
            Route::post('/run/{run}/keputusan', [PenilaianPantasController::class, 'decide'])
                ->middleware('permission:sppt.kelulusan');
        });

        Route::get('/permohonan/summary', [PermohonanController::class, 'summary']);
        Route::post('/permohonan/{permohonan}/workflow', [PermohonanController::class, 'processWorkflow'])
            ->middleware('permission:sppt.edit');
        Route::post('/permohonan/ocr/extract', [PermohonanController::class, 'extractFormOcr'])
            ->middleware('permission:sppt.edit');
        Route::post('/permohonan/risk-scoring/score', [AiRiskScoringController::class, 'score']);
        Route::post('/permohonan/{permohonan}/risk-scoring', [AiRiskScoringController::class, 'scorePermohonan']);
        Route::post('/permohonan/credit-scoring/score', [AiCreditScoringController::class, 'score']);
        Route::post('/permohonan/{permohonan}/credit-scoring', [AiCreditScoringController::class, 'scorePermohonan']);
        Route::get('/pembiayaan-formula/defaults', [PembiayaanFormulaController::class, 'defaults']);
        Route::post('/pembiayaan-formula/calculate', [PembiayaanFormulaController::class, 'calculate']);
        Route::post('/permohonan/dokumen/verify', [PermohonanController::class, 'verifyDocument'])
            ->middleware('permission:sppt.edit');
        Route::post('/permohonan/dokumen/classify', [PermohonanController::class, 'classifyDocument'])
            ->middleware('permission:sppt.edit');
        Route::post('/permohonan/{permohonan}/dokumen', [PermohonanController::class, 'uploadDocument'])
            ->middleware('permission:sppt.edit');
        Route::patch('/permohonan/{permohonan}/dokumen/{attachmentId}', [PermohonanController::class, 'updateDocumentClass'])
            ->middleware('permission:sppt.edit');
        Route::post('/permohonan/{permohonan}/penilaian', [PermohonanController::class, 'savePenilaian'])
            ->middleware('permission:sppt.edit');
        Route::get('/permohonan/{permohonan}/surat-tawaran', [PermohonanController::class, 'offerLetter']);
        Route::get('/permohonan/{permohonan}/dokumen/{attachmentId}', [PermohonanController::class, 'showDocument']);
        Route::delete('/permohonan/{permohonan}/dokumen/{attachmentId}', [PermohonanController::class, 'deleteDocument'])
            ->middleware('permission:sppt.edit');
        Route::apiResource('permohonan', PermohonanController::class)
            ->middlewareFor('store', 'permission:sppt.create')
            ->middlewareFor('update', 'permission:sppt.edit')
            ->middlewareFor('destroy', 'permission:sppt.delete');

        Route::get('/usahawan/summary', [UsahawanController::class, 'summary']);
        Route::apiResource('usahawan', UsahawanController::class)
            ->middlewareFor('store', 'permission:sppt.create')
            ->middlewareFor('update', 'permission:sppt.edit')
            ->middlewareFor('destroy', 'permission:sppt.delete');

        Route::get('/akaun/summary', [AkaunController::class, 'summary']);
        Route::get('/akaun', [AkaunController::class, 'index']);
        Route::get('/akaun/{id}', [AkaunController::class, 'show']);
        Route::get('/akaun/{id}/jadual', [JadualAnsuranController::class, 'index'])->whereNumber('id');

        Route::get('/penjadualan', [PenjadualanController::class, 'index']);
        Route::post('/penjadualan', [PenjadualanController::class, 'store'])
            ->middleware('permission:sppt.create');
        Route::get('/penjadualan/{id}', [PenjadualanController::class, 'show'])->whereNumber('id');
        Route::post('/penjadualan/{id}/approve', [PenjadualanController::class, 'approve'])
            ->middleware('permission:sppt.edit')->whereNumber('id');

        Route::get('/bayaran/saluran', [BayaranController::class, 'saluran']);
        Route::get('/bayaran', [BayaranController::class, 'index']);
        Route::post('/bayaran', [BayaranController::class, 'store'])
            ->middleware('permission:sppt.create');
        Route::post('/bayaran/batch', [BayaranController::class, 'batch'])
            ->middleware('permission:sppt.create');
        Route::get('/bayaran/batches', [BayaranController::class, 'batches']);

        Route::get('/tawidh/config', [TawidhController::class, 'config']);
        Route::post('/tawidh/recalculate', [TawidhController::class, 'recalculateAll'])
            ->middleware('permission:sppt.edit');
        Route::post('/tawidh/recalculate/{akaunId}', [TawidhController::class, 'recalculateAccount'])
            ->middleware('permission:sppt.edit')->whereNumber('akaunId');
        Route::get('/tawidh/tunggakan', [TawidhController::class, 'tunggakanSummary']);

        Route::get('/koleksi/config', [KoleksiController::class, 'config']);
        Route::get('/koleksi/klasifikasi', [KoleksiController::class, 'klasifikasiIndex']);
        Route::get('/koleksi/klasifikasi/summary', [KoleksiController::class, 'klasifikasiSummary']);
        Route::post('/koleksi/klasifikasi/recalculate', [KoleksiController::class, 'recalculateKlasifikasi'])
            ->middleware('permission:sppt.edit');
        Route::get('/koleksi/notis', [KoleksiController::class, 'notisIndex']);
        Route::post('/koleksi/notis/process', [KoleksiController::class, 'processDunning'])
            ->middleware('permission:sppt.edit');
        Route::get('/koleksi/notis/{id}/pdf', [KoleksiController::class, 'notisPdf'])->whereNumber('id');

        Route::get('/pengeluaran-dana/summary', [PengeluaranDanaController::class, 'summary']);
        Route::get('/pengeluaran-dana', [PengeluaranDanaController::class, 'index']);
        Route::get('/jaminan/summary', [JaminanController::class, 'summary']);
        Route::get('/jaminan', [JaminanController::class, 'index']);
        Route::get('/kutipan', [KutipanController::class, 'index']);
    });

    // AINA User Chat — KB-only assistant for end users
    Route::middleware(['permission:'.Permission::CHAT_USE, 'user_chat_access'])
        ->prefix('chat/user')
        ->group(function () {
            Route::post('/sessions', [UserChatController::class, 'newUserChatSession']);
            Route::put('/sessions/{id}', [UserChatController::class, 'updateUserChatSession'])->whereNumber('id');
            Route::post('/sessions/{id}/messages', [UserChatController::class, 'sendUserChatMessage'])->whereNumber('id');
            Route::get('/sessions/{id}', [UserChatController::class, 'getUserChatSession'])->whereNumber('id');
            Route::get('/sessions', [UserChatController::class, 'myUserChatSessions']);
            Route::delete('/sessions/{id}', [UserChatController::class, 'deleteUserChatSession'])->whereNumber('id');
            Route::post('/sessions/{id}/favorite', [UserChatController::class, 'toggleUserChatSessionFavorite'])->whereNumber('id');
            Route::get('/sessions/{id}/messages/search', [UserChatController::class, 'searchUserChatMessages'])->whereNumber('id');
            Route::get('/favorites', [UserChatController::class, 'userChatFavorites']);
            Route::post('/messages/{id}/favorite', [UserChatController::class, 'toggleUserChatMessageFavorite'])->whereNumber('id');
            Route::get('/suggestions', [UserChatController::class, 'userChatSuggestions']);
        });

    // Workflow Configuration — Pentadbiran
    Route::prefix('workflow-configuration')->group(function () {
        Route::get('/', [WorkflowConfigurationController::class, 'index']);
        Route::get('/reroute-process-options', [WorkflowConfigurationController::class, 'rerouteProcessOptions']);
        Route::get('/status-lookup', [WorkflowConfigurationController::class, 'statusLookup']);
        Route::get('/peranan-rujukan', [WorkflowConfigurationController::class, 'roleLookup']);
        Route::get('/{code}/processes', [WorkflowConfigurationController::class, 'processes']);
        Route::get('/processes/{id}/details', [WorkflowConfigurationController::class, 'details'])->whereNumber('id');
        Route::get('/processes/{id}/authorized-roles', [WorkflowConfigurationController::class, 'authorizedRoles'])->whereNumber('id');

        Route::post('/workflow', [WorkflowConfigurationController::class, 'storeWorkflow']);
        Route::put('/workflow/{code}', [WorkflowConfigurationController::class, 'updateWorkflow']);
        Route::delete('/workflow/{code}', [WorkflowConfigurationController::class, 'destroyWorkflow']);

        Route::post('/process', [WorkflowConfigurationController::class, 'storeProcess']);
        Route::put('/process/{id}', [WorkflowConfigurationController::class, 'updateProcess'])->whereNumber('id');
        Route::delete('/process/{id}', [WorkflowConfigurationController::class, 'destroyProcess'])->whereNumber('id');

        Route::post('/process-detail', [WorkflowConfigurationController::class, 'storeProcessDetail']);
        Route::put('/process-detail/{id}', [WorkflowConfigurationController::class, 'updateProcessDetail'])->whereNumber('id');
        Route::delete('/process-detail/{id}', [WorkflowConfigurationController::class, 'destroyProcessDetail'])->whereNumber('id');

        Route::post('/authorized-role', [WorkflowConfigurationController::class, 'storeAuthorizedRole']);
        Route::put('/authorized-role/{id}', [WorkflowConfigurationController::class, 'updateAuthorizedRole'])->whereNumber('id');
        Route::delete('/authorized-role/{id}', [WorkflowConfigurationController::class, 'destroyAuthorizedRole'])->whereNumber('id');
    });
});
