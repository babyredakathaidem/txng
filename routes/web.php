<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

// ── Core controllers ───────────────────────────────────────────────────────
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicController;

// ── Batch controllers ──────────────────────────────────────────────────────
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BatchSplitController;
use App\Http\Controllers\BatchMergeController;
use App\Http\Controllers\BatchRecallController;
use App\Http\Controllers\BatchTransferController;       // kept for BatchTransfer model compat
use App\Http\Controllers\BatchTransformationController;

// ── Event controllers (event-centric architecture) ────────────────────────
use App\Http\Controllers\TraceEventController;
use App\Http\Controllers\TransferEventController;
use App\Http\Controllers\ObservationEventController;
use App\Http\Controllers\TransformationEventController;

// ── Supporting controllers ─────────────────────────────────────────────────
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductProcessController;
use App\Http\Controllers\TraceLocationController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\QrAdminController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\VietmapController;

// ── Onboarding ─────────────────────────────────────────────────────────────
use App\Http\Controllers\OnboardingEnterpriseController;
use App\Http\Controllers\OnboardingEnterpriseStatusController;

// ── Enterprise admin ───────────────────────────────────────────────────────
use App\Http\Controllers\Enterprise\UserController as EnterpriseUserController;
use App\Http\Controllers\Enterprise\SettingsController as EnterpriseSettingsController;

// ── System admin ───────────────────────────────────────────────────────────
use App\Http\Controllers\Sys\EnterpriseApprovalController;

// ── API ────────────────────────────────────────────────────────────────────
use App\Http\Controllers\Api\EpcisController;

/*
|=============================================================================
| PUBLIC — No auth required
|=============================================================================
*/

Route::get('/',           [PublicController::class, 'home'])       ->name('home');
Route::get('/san-pham',   [PublicController::class, 'products'])   ->name('public.products');
Route::get('/categories', [PublicController::class, 'categories']) ->name('public.categories');
Route::get('/verify',     [PublicController::class, 'verify'])     ->name('public.verify');

// ── QR scan — public token ────────────────────────────────────────────────
Route::get('/t/{token}',         [QrScanController::class, 'gatePublic'])   ->name('trace.gate.public');
Route::post('/t/{token}/resolve',[QrScanController::class, 'resolvePublic'])->name('trace.resolve.public');

// ── QR scan — private token ───────────────────────────────────────────────
Route::get('/v/{token}',          [QrScanController::class, 'gatePrivate'])    ->name('trace.gate.private');
Route::post('/v/{token}/resolve', [QrScanController::class, 'resolvePrivate']) ->name('trace.resolve.private');

// ── GS1 Digital Link ──────────────────────────────────────────────────────
Route::get('/01/{gtin}/10/{lot}', [QrScanController::class, 'resolveGs1Public'])
    ->name('qr.gs1.public')
    ->where(['gtin' => '\d{8,14}', 'lot' => '[A-Za-z0-9\-_.%]+']);

Route::get('/gs1/{gtin}/{lot}', [QrScanController::class, 'gateGs1Public'])
    ->name('qr.gs1.gate')
    ->where(['gtin' => '\d{8,14}', 'lot' => '[A-Za-z0-9\-_.%]+']);

// ── QR Bước sản xuất — dán trực tiếp lên vùng trồng / kho ───────────────
Route::get('/step/{token}', function (string $token) {
    $event = \App\Models\TraceEvent::where('event_token', $token)->first();

    if (! $event) {
        return Inertia::render('Trace/StepNotFound');
    }

    $event->load(['batch.product', 'batch.enterprise']);

    return Inertia::render('Trace/StepShow', [
        'event' => [
            'id'            => $event->id,
            'event_token'   => $event->event_token,
            'cte_code'      => $event->cte_code,
            'event_time'    => optional($event->event_time)->format('d/m/Y H:i'),
            'who_name'      => $event->who_name,
            'where_address' => $event->where_address,
            'kde_data'      => $event->kde_data,
            'attachments'   => $event->attachments,
            'note'          => $event->note,
            'ipfs_cid'      => $event->ipfs_cid,
            'ipfs_url'      => $event->ipfs_url,
            'content_hash'  => $event->content_hash,
            'status'        => $event->status,
            'batch'         => $event->batch ? [
                'code'         => $event->batch->code,
                'product_name' => $event->batch->product?->name ?? $event->batch->product_name,
                'enterprise'   => ['name' => $event->batch->enterprise?->name],
            ] : null,
        ],
    ]);
})->name('step.show');

// ── Xác minh tính toàn vẹn — public JSON endpoint (3 lớp: Fabric + IPFS + DB) ──
// Dùng bởi VerifyIntegrityBtn.vue (fetch inline, không navigate)
Route::get('/verify/integrity/{id}', [TraceEventController::class, 'verifyIntegrity'])
    ->name('verify.integrity')
    ->where('id', '\d+');

// ── Legacy IPFS verify — giữ backward compat (trả JSON thô) ─────────────
Route::get('/verify/ipfs/{cid}', [TraceEventController::class, 'verifyIpfs'])
    ->name('verify.ipfs');

// ── EPCIS 2.0 API ─────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {
    Route::get('/epcis/events/{batch_id}', [EpcisController::class, 'events'])
        ->name('api.epcis.events')
        ->where('batch_id', '\d+');
});

/*
|=============================================================================
| AUTH ONLY — no tenant scope (onboarding, profile, email verify)
|=============================================================================
*/

Route::middleware(['auth'])->group(function () {

    // ── Profile ───────────────────────────────────────────────────────────
    Route::get('/profile',    [ProfileController::class, 'edit'])   ->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update']) ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Email verify helpers ──────────────────────────────────────────────
    Route::get('email-verified', function () {
        return Inertia::render('Auth/VerifyEmailSuccess');
    })->name('email.verified.success');

    Route::get('auth/email-status', function (Request $request) {
        return response()->json(['verified' => (bool) $request->user()?->hasVerifiedEmail()]);
    })->name('auth.email.status');

    // ── Onboarding DN ─────────────────────────────────────────────────────
    Route::get('/onboarding/enterprise',          [OnboardingEnterpriseController::class, 'create']) ->name('onboarding.enterprise.create');
    Route::post('/onboarding/enterprise',         [OnboardingEnterpriseController::class, 'store'])  ->name('onboarding.enterprise.store');
    Route::get('/onboarding/enterprise/status',   [OnboardingEnterpriseStatusController::class, 'status'])  ->name('onboarding.enterprise.status');
    Route::get('/onboarding/enterprise/pending',  [OnboardingEnterpriseStatusController::class, 'pending'])  ->name('onboarding.enterprise.pending');
    Route::get('/onboarding/enterprise/rejected', [OnboardingEnterpriseStatusController::class, 'rejected']) ->name('onboarding.enterprise.rejected');
    Route::get('/onboarding/enterprise/blocked',  [OnboardingEnterpriseStatusController::class, 'blocked'])  ->name('onboarding.enterprise.blocked');
});

/*
|=============================================================================
| DASHBOARD
|=============================================================================
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant.ready', 'tenant'])
    ->name('dashboard');

/*
|=============================================================================
| INTERNAL APP — auth + verified + tenant.ready + tenant scope
|=============================================================================
*/

Route::middleware(['auth', 'verified', 'tenant.ready', 'tenant'])->group(function () {

    // ══ PRODUCTS ═════════════════════════════════════════════════════════════
    Route::get('/products',              [ProductController::class, 'index'])  ->name('products.index');
    Route::post('/products',             [ProductController::class, 'store'])  ->name('products.store');
    Route::post('/products/{product}',   [ProductController::class, 'update']) ->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/products/{product}',    [ProductController::class, 'show'])   ->name('products.show');

    // ── Product processes (quy trình sản xuất theo sản phẩm) ─────────────
    Route::get('/products/{product}/processes',            [ProductProcessController::class, 'index'])  ->name('products.processes.index');
    Route::post('/products/{product}/processes/sync',      [ProductProcessController::class, 'sync'])   ->name('products.processes.sync');
    Route::delete('/products/{product}/processes/{step}',  [ProductProcessController::class, 'destroy'])->name('products.processes.destroy');

    // ══ BATCHES ═══════════════════════════════════════════════════════════════
    Route::get('/batches',            [BatchController::class, 'index'])  ->name('batches.index');
    Route::post('/batches',           [BatchController::class, 'store'])  ->name('batches.store');
    Route::put('/batches/{batch}',    [BatchController::class, 'update']) ->name('batches.update');
    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
    Route::get('/batches/{batch}/lineage', [BatchController::class, 'lineage'])->name('batches.lineage');

    // ── Batch split ───────────────────────────────────────────────────────
    Route::get('/batches/{batch}/split',  [BatchSplitController::class, 'show'])  ->name('batches.split.show');
    Route::post('/batches/{batch}/split', [BatchSplitController::class, 'store']) ->name('batches.split.store');

    // ── Batch merge ───────────────────────────────────────────────────────
    Route::get('/batches/merge',  [BatchMergeController::class, 'show'])  ->name('batches.merge.show');
    Route::post('/batches/merge', [BatchMergeController::class, 'store']) ->name('batches.merge.store');

    // ── Batch transformation (chế biến) ──────────────────────────────────
    Route::get('/batches/transform', [TransformationEventController::class, 'create'])->name('batches.transform.show');
    Route::post('/batches/transform', [TransformationEventController::class, 'store'])->name('batches.transform.store');

    // ── Batch transfer — compat redirect → event-centric pages ───────────
    // Giữ lại để không break các link cũ trong code, redirect sang trang mới
    Route::get('/batches/{batch}/transfer', function (\App\Models\Batch $batch) {
        return redirect()->route('transfer.out.create');
    })->name('batches.transfer.show');

    // ── Batch recall ──────────────────────────────────────────────────────
    Route::post('/batches/{batch}/recall',         [BatchRecallController::class, 'store'])  ->name('batches.recall.store');
    Route::patch('/batches/{batch}/recall/resolve',[BatchRecallController::class, 'resolve'])->name('batches.recall.resolve');

    // ── QR admin ──────────────────────────────────────────────────────────
    Route::get('/batches/{batch}/qrs',                        [QrAdminController::class, 'index'])           ->name('batches.qrs');
    Route::post('/batches/{batch}/qrs/ensure',                [QrAdminController::class, 'ensure'])          ->name('batches.qrs.ensure');
    Route::post('/qrcodes/{qrcode}/configure-public',         [QrAdminController::class, 'configurePublic']) ->name('qrcodes.configurePublic');

    // ══ TRACE EVENTS (general — index, store, update, destroy, publish) ════
    Route::get('/events',                            [TraceEventController::class, 'index'])           ->name('events.index');
    Route::post('/events',                           [TraceEventController::class, 'store'])           ->name('events.store');
    Route::put('/events/{traceEvent}',               [TraceEventController::class, 'update'])          ->name('events.update');
    Route::delete('/events/{traceEvent}',            [TraceEventController::class, 'destroy'])         ->name('events.destroy');
    Route::post('/events/{traceEvent}/publish',      [TraceEventController::class, 'publish'])         ->name('events.publish');
    Route::post('/events/{traceEvent}/attachments',  [TraceEventController::class, 'uploadAttachment'])->name('events.attachments.store');

    // ══ TRANSFER EVENTS ════════════════════════════════════════════════════
    // Create pages
    Route::get('/events/create/transfer-in', function (Request $request) {
        $enterpriseId = $request->user()->enterprise_id;
        $locations = \App\Models\TraceLocation::where('enterprise_id', $enterpriseId)
            ->where('status', 'active')
            ->get(['id', 'name', 'gln', 'address_detail', 'province', 'ai_type']);
        $products = \App\Models\Product::where('enterprise_id', $enterpriseId)
            ->where('status', 'active')
            ->get(['id', 'name', 'gtin', 'unit']);
        return Inertia::render('Events/CreateTransferIn', [
            'locations' => $locations,
            'products'  => $products,
        ]);
    })->name('transfer.in.create');

    Route::get('/events/create/transfer-out', function (Request $request) {
        $enterpriseId = $request->user()->enterprise_id;
        $enterprises = \App\Models\Enterprise::where('id', '!=', $enterpriseId)
            ->where('status', 'approved')
            ->get(['id', 'name', 'code', 'gln', 'province', 'district', 'address_detail']);
        $batches = \App\Models\Batch::where('enterprise_id', $enterpriseId)
            ->where('status', 'active')
            ->with('enterprise:id,name')
            ->get();
        return Inertia::render('Events/CreateTransferOut', [
            'batches'     => $batches,
            'enterprises' => $enterprises,
        ]);
    })->name('transfer.out.create');

    // API actions
    Route::post('/events/transfer-in',             [TransferEventController::class, 'storeTransferIn']) ->name('events.transfer.in');
    Route::post('/events/transfer-out',            [TransferEventController::class, 'storeTransferOut'])->name('events.transfer.out');
    Route::post('/events/transfer/{event}/accept', [TransferEventController::class, 'acceptTransfer'])  ->name('events.transfer.accept');
    Route::post('/events/transfer/{event}/reject', [TransferEventController::class, 'rejectTransfer'])  ->name('events.transfer.reject');
    Route::get('/events/transfer/pending',         [TransferEventController::class, 'pendingIndex'])     ->name('events.transfer.pending');

    // Alias — dùng trong CreateTransferOut.vue (form submit + redirect)
    Route::post('/transfer/out',    [TransferEventController::class, 'storeTransferOut'])->name('transfer.out');
    Route::get('/transfer/pending', [TransferEventController::class, 'pendingIndex'])    ->name('transfer.pending');

    // ══ OBSERVATION EVENTS ════════════════════════════════════════════════
    Route::get('/events/create/observation',             [ObservationEventController::class, 'create']) ->name('observation-events.create');
    Route::post('/events/observation',                   [ObservationEventController::class, 'store'])  ->name('observation-events.store');
    Route::put('/events/observation/{traceEvent}',       [ObservationEventController::class, 'update']) ->name('observation-events.update');
    Route::delete('/events/observation/{traceEvent}',    [ObservationEventController::class, 'destroy'])->name('observation-events.destroy');

    // ══ TRANSFORMATION EVENTS ════════════════════════════════════════════
    Route::get('/events/create/transformation',          [TransformationEventController::class, 'create']) ->name('transformation-events.create');
    Route::post('/events/transformation',                [TransformationEventController::class, 'store'])  ->name('transformation-events.store');
    Route::get('/events/transformation/{traceEvent}',    [TransformationEventController::class, 'show'])   ->name('transformation-events.show');
    Route::delete('/events/transformation/{traceEvent}', [TransformationEventController::class, 'destroy'])->name('transformation-events.destroy');

    // ══ TRACE LOCATIONS (địa điểm GS1 AI 410-417) ════════════════════════
    Route::get('/trace-locations',                              [TraceLocationController::class, 'index'])        ->name('trace-locations.index');
    Route::post('/trace-locations',                             [TraceLocationController::class, 'store'])        ->name('trace-locations.store');
    Route::put('/trace-locations/{traceLocation}',              [TraceLocationController::class, 'update'])       ->name('trace-locations.update');
    Route::delete('/trace-locations/{traceLocation}',           [TraceLocationController::class, 'destroy'])      ->name('trace-locations.destroy');
    Route::post('/trace-locations/generate-gln',                [TraceLocationController::class, 'generateGln']) ->name('trace-locations.generate-gln');

    // ══ CERTIFICATES (VietGAP, HACCP, ISO...) ════════════════════════════
    Route::prefix('certificates')->name('certificates.')->group(function () {
        Route::get('/',                [CertificateController::class, 'index'])  ->name('index');
        Route::post('/',               [CertificateController::class, 'store'])  ->name('store');
        Route::put('/{certificate}',   [CertificateController::class, 'update']) ->name('update');
        Route::delete('/{certificate}',[CertificateController::class, 'destroy'])->name('destroy');
    });

    // ══ ENTERPRISE ADMIN ══════════════════════════════════════════════════
    Route::get('/enterprise/users',           [EnterpriseUserController::class, 'index'])  ->name('enterprise.users.index');
    Route::post('/enterprise/users',          [EnterpriseUserController::class, 'store'])  ->name('enterprise.users.store');
    Route::put('/enterprise/users/{user}',    [EnterpriseUserController::class, 'update']) ->name('enterprise.users.update');
    Route::delete('/enterprise/users/{user}', [EnterpriseUserController::class, 'destroy'])->name('enterprise.users.destroy');

    Route::get('/enterprise/settings', [EnterpriseSettingsController::class, 'show'])  ->name('enterprise.settings.show');
    Route::put('/enterprise/settings', [EnterpriseSettingsController::class, 'update'])->name('enterprise.settings.update');

    // ══ VIETMAP PROXY ════════════════════════════════════════════════════
    Route::post('/vietmap/autocomplete', [VietmapController::class, 'autocomplete'])->name('vietmap.autocomplete');
    Route::post('/vietmap/place',        [VietmapController::class, 'place'])        ->name('vietmap.place');

    // ══ DEV TOOLS (loại bỏ khi production) ═══════════════════════════════
    Route::get('/dev/vietmap-place-test', function () {
        return Inertia::render('Dev/VietmapPlaceTest');
    })->name('dev.vietmap-place-test');
});

/*
|=============================================================================
| API — auth + tenant, trả JSON
|=============================================================================
*/

Route::middleware(['auth', 'verified', 'tenant.ready', 'tenant'])
    ->prefix('api')
    ->group(function () {
        // CTE templates theo category + completeness check
        Route::get('/cte-templates', [TraceEventController::class, 'getTemplates'])
            ->name('api.cte-templates');
    });

// API endpoints standalone (không prefix /api để tránh conflict với route cache)
Route::get('/api/certificates/list', [CertificateController::class, 'listForBatch'])
    ->name('api.certificates.list')
    ->middleware(['auth', 'tenant.ready', 'tenant']);

Route::get('/api/trace-locations', [TraceLocationController::class, 'listForEvent'])
    ->name('api.trace-locations.list')
    ->middleware(['auth', 'tenant.ready', 'tenant']);

/*
|=============================================================================
| SYSTEM ADMIN — super admin only
|=============================================================================
*/

Route::middleware(['auth', 'super'])
    ->prefix('sys')
    ->group(function () {
        // Enterprise management
        Route::get('/enterprises',                      [EnterpriseApprovalController::class, 'index'])  ->name('sys.enterprises.index');
        Route::get('/enterprises/{enterprise}',         [EnterpriseApprovalController::class, 'show'])   ->name('sys.enterprises.show');
        Route::post('/enterprises/{enterprise}/approve',[EnterpriseApprovalController::class, 'approve'])->name('sys.enterprises.approve');
        Route::post('/enterprises/{enterprise}/reject', [EnterpriseApprovalController::class, 'reject']) ->name('sys.enterprises.reject');
        Route::post('/enterprises/{enterprise}/block',  [EnterpriseApprovalController::class, 'block'])  ->name('sys.enterprises.block');
        Route::post('/enterprises/{enterprise}/unblock',[EnterpriseApprovalController::class, 'unblock'])->name('sys.enterprises.unblock');

        // Global config (TCVN & KDE standards)
        Route::get('/config',                           [\App\Http\Controllers\Sys\GlobalConfigController::class, 'index'])         ->name('sys.config.index');
        Route::post('/config/categories',               [\App\Http\Controllers\Sys\GlobalConfigController::class, 'storeCategory']) ->name('sys.config.categories.store');
        Route::put('/config/cte/{template}',            [\App\Http\Controllers\Sys\GlobalConfigController::class, 'updateCte'])     ->name('sys.config.cte.update');
        Route::get('/stats',                            [\App\Http\Controllers\Sys\GlobalConfigController::class, 'systemStats'])   ->name('sys.stats');

        // User management (global)
        Route::get('/users',              [\App\Http\Controllers\Sys\UserManagementController::class, 'index'])       ->name('sys.users.index');
        Route::put('/users/{user}',       [\App\Http\Controllers\Sys\UserManagementController::class, 'update'])      ->name('sys.users.update');
        Route::post('/users/{user}/toggle',[\App\Http\Controllers\Sys\UserManagementController::class, 'toggleStatus'])->name('sys.users.toggle');
    });

/*
|=============================================================================
| AUTH ROUTES (login, register, reset password...)
|=============================================================================
*/

require __DIR__ . '/auth.php';