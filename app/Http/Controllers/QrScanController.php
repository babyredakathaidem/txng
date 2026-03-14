<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\Qrcode;
use App\Models\QrScanLog;
use App\Models\TraceEvent;
use App\Services\GS1Service;
use App\Services\LineageService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QrScanController extends Controller
{
    public function __construct(
        private LineageService $lineageService,
        private QrCodeService  $qrCodeService,
        private GS1Service     $gs1Service,
    ) {}

    public function gatePublic(string $token)
    {
        return Inertia::render('Trace/GatePublic', ['token' => $token]);
    }

    public function gatePrivate(string $token)
    {
        return Inertia::render('Trace/GatePrivate', ['token' => $token]);
    }

    // ── Eager load relations dùng chung ──────────────────────────────
    /**
     * BỎ QUA GLOBAL SCOPES (TenantScope) vì hành động quét mã là công khai.
     */
    private function qrWithRelations(): \Illuminate\Database\Eloquent\Builder
    {
        return Qrcode::withoutGlobalScopes()->with([
            'batch'                        => fn($q) => $q->withoutGlobalScopes(),
            'batch.product',
            'batch.product.category',
            'batch.enterprise',
            'batch.activeRecall',
            'batch.activeRecall.recalledBy',
            'batch.certificates',
            'batch.originEvent:id,event_code',   // thêm để lấy origin_event_code
        ]);
    }

    public function resolvePublic(Request $request, string $token)
    {
        $qr = $this->qrWithRelations()
            ->where('type', 'public')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'public', $token, 'token_not_found');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR không hợp lệ',
                'message' => 'QR công khai không tồn tại hoặc đã bị thu hồi.',
            ]);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if ($lat === null || $lng === null) {
            return $this->gatePublic($token);
        }

        if ($qr->allowed_lat === null || $qr->allowed_lng === null || $qr->allowed_radius_m === null) {
            $this->logScan($request, $qr, $lat, $lng, null, 'blocked', 'public_not_configured');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR chưa cấu hình',
                'message' => 'QR công khai chưa được cấu hình điểm phát hành.',
            ]);
        }

        $distance = (int) round($this->distanceMeters(
            (float) $lat, (float) $lng,
            (float) $qr->allowed_lat, (float) $qr->allowed_lng
        ));

        $ok = $distance <= (int) $qr->allowed_radius_m;

        if (!$ok) {
            $this->logScan($request, $qr, $lat, $lng, $distance, 'blocked', 'geo_mismatch');
            return Inertia::render('Trace/Blocked', [
                'title'      => 'Sai vị trí quét',
                'message'    => "QR công khai chỉ hợp lệ trong phạm vi {$qr->allowed_radius_m}m quanh '{$qr->place_name}'.",
                'distance_m' => $distance,
                'radius_m'   => (int) $qr->allowed_radius_m,
            ]);
        }

        $this->logScan($request, $qr, $lat, $lng, $distance, 'allowed', null);

        return Inertia::render('Trace/Show', [
            'mode'       => 'public',
            'batch'      => $this->formatBatch($qr->batch),
            'events'     => $this->loadEvents($qr->batch),
            'place_name' => $qr->place_name,
        ]);
    }

    public function resolvePrivate(Request $request, string $token)
    {
        $qr = $this->qrWithRelations()
            ->where('type', 'private')
            ->where('token', $token)
            ->first();

        if (!$qr) {
            $this->logInvalid($request, 'private', $token, 'token_not_found_or_deleted');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR không hợp lệ',
                'message' => 'QR riêng tư không tồn tại hoặc đã hết hiệu lực.',
            ]);
        }

        $now = now();

        if ($qr->expires_at && $now->greaterThan($qr->expires_at)) {
            $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'expired', 'private_expired');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR đã hết hiệu lực',
                'message' => 'Mã QR này chỉ có hiệu lực 24 giờ kể từ lần quét đầu tiên để đảm bảo tính riêng tư.',
            ]);
        }

        if (!$qr->first_scanned_at) {
            $qr->first_scanned_at = $now;
            $qr->expires_at       = $now->copy()->addHours(24);
            $qr->save();
        }

        $this->logScan($request, $qr, $request->input('lat'), $request->input('lng'), null, 'allowed', null);

        return Inertia::render('Trace/Show', [
            'mode'       => 'private',
            'batch'      => $this->formatBatch($qr->batch),
            'events'     => $this->loadEvents($qr->batch),
            'expires_at' => optional($qr->expires_at)->toDateTimeString(),
        ]);
    }

    public function resolveGs1Public(Request $request, string $gtin, string $lot)
    {
        $lotDecoded = urldecode($lot);
        $gtinPadded = str_pad($gtin, 14, '0', STR_PAD_LEFT);
        $type = $request->query('type', 'public');

        $qr = $this->qrWithRelations()
            ->where('gtin_used', $gtinPadded)
            ->whereHas('batch', function ($q) use ($lotDecoded) {
                $q->withoutGlobalScopes()
                  ->where(function($sq) use ($lotDecoded) {
                      $sq->where('code', 'like', '%' . $lotDecoded . '%')
                         ->orWhere('code', $lotDecoded);
                  });
            })
            ->orderByRaw("CASE WHEN type = ? THEN 0 ELSE 1 END", [$type])
            ->first();

        if (! $qr) {
            $this->logInvalid($request, $type, "gs1:{$gtin}/10/{$lot}", 'gs1_not_found');
            return Inertia::render('Trace/Blocked', [
                'title'   => 'QR không tìm thấy',
                'message' => "Không tìm thấy dữ liệu cho sản phẩm với mã GS1 này.",
            ]);
        }

        $request->merge(['_gs1_resolved' => true]);

        if ($qr->type === 'private') {
            return $this->resolvePrivate($request, $qr->token);
        }
        return $this->resolvePublic($request, $qr->token);
    }

    // ══════════════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════════════

    private function formatBatch($batch): array
    {
        if (!$batch) return [];

        $recallDetails     = null;
        $isCascadeRecalled = false;
        $parentBatchCode   = null;

        if ($batch->status === 'recalled') {
            $activeRecall = $batch->activeRecall;
            if ($activeRecall) {
                $recallDetails = [
                    'reason'          => $activeRecall->reason,
                    'notice_content'  => $activeRecall->notice_content,
                    'recalled_at'     => optional($activeRecall->recalled_at)->format('H:i d/m/Y'),
                    'recalled_by'     => $activeRecall->recalledBy?->name ?? 'Hệ thống',
                    'ipfs_cid'        => $activeRecall->ipfs_cid,
                ];
                if ($activeRecall->parent_recall_id) {
                    $isCascadeRecalled = true;
                    if (preg_match('/\[Cascade từ lô ([^\]]+)\]/', $activeRecall->reason, $matches)) {
                        $parentBatchCode = $matches[1];
                    }
                }
            }
        }

        // GS1 AI(01) — GTIN-14 có ký hiệu GS1 AI
        $gtin14    = $batch->gtin_cached ?? null;
        $ai01Code  = $gtin14 ? "(01){$gtin14}" : null;

        return [
            'id'              => $batch->id,
            'code'            => $batch->code,
            'production_date' => optional($batch->production_date)->format('d/m/Y'),
            'expiry_date'     => optional($batch->expiry_date)->format('d/m/Y'),
            'quantity'        => $batch->quantity,
            'unit'            => $batch->unit,
            'status'          => $batch->status,
            'batch_type'      => $batch->batch_type,
            'recall_details'  => $recallDetails,
            'is_cascade_recalled' => $isCascadeRecalled,
            'parent_batch_code'   => $parentBatchCode,

            // Trường mới thêm
            'origin_event_code' => $batch->originEvent?->event_code,
            'gs1_128_label'     => $batch->gs1_128_label,
            'ai01_code'         => $ai01Code,

            'certificates' => $batch->certificates?->map(fn($c) => [
                'name'         => $c->name,
                'organization' => $c->organization,
                'image_url'    => $c->image_url,
                'is_expired'   => $c->isExpired(),
            ]),
            'product' => $batch->product ? [
                'name'       => $batch->product->name,
                'gtin'       => $batch->product->gtin,
                'image_path' => $batch->product->image_path,
                'category'   => $batch->product->category ? [
                    'name_vi' => $batch->product->category->name_vi,
                    'icon'    => $batch->product->category->icon,
                ] : null,
            ] : null,
            'enterprise' => $batch->enterprise ? [
                'name'    => $batch->enterprise->name,
                'address' => $batch->enterprise->full_address,
                'phone'   => $batch->enterprise->phone,
            ] : null,
        ];
    }

    /**
     * Load published events của toàn bộ cây lineage — dùng LineageService.
     */
    private function loadEvents($batch): array
    {
        if (!$batch) return [];

        return $this->lineageService
            ->loadPublishedEvents($batch->id)
            ->map(fn($event) => $this->lineageService->formatEventForPublic($event))
            ->values()
            ->all();
    }

    // ── Logging ──────────────────────────────────────────────────────

    private function logScan($request, $qr, $lat, $lng, $distance, $decision, $reason): void
    {
        QrScanLog::create([
            'qrcode_id'          => $qr->id,
            'enterprise_id'      => $qr->enterprise_id,
            'batch_id'           => $qr->batch_id,
            'qr_type'            => $qr->type,
            'token'              => $qr->token,
            'expected_place_name'=> $qr->place_name,
            'lat'                => $lat,
            'lng'                => $lng,
            'distance_m'         => $distance,
            'ip'                 => $request->ip(),
            'user_agent'         => substr((string) $request->userAgent(), 0, 500),
            'decision'           => $decision,
            'reason'             => $reason,
            'scanned_at'         => now(),
        ]);
    }

    private function logInvalid($request, $type, $token, $reason): void
    {
        QrScanLog::create([
            'qr_type'    => $type,
            'token'      => $token,
            'ip'         => $request->ip(),
            'decision'   => 'invalid',
            'reason'     => $reason,
            'scanned_at' => now(),
        ]);
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
