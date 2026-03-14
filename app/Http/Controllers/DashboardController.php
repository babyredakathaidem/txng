<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\TraceEvent;
use App\Models\QrScanLog;
use App\Models\BatchRecall;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) $request->user()->enterprise_id;

        // ── Tổng quan ─────────────────────────────────────────────
        $totalProducts = Product::where('enterprise_id', $tenantId)->count();
        $totalLocations = \App\Models\TraceLocation::where('enterprise_id', $tenantId)->count();

        $batchStats = Batch::where('enterprise_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'recalled' THEN 1 ELSE 0 END) as recalled,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
            ")
            ->first();

        // FIX: Thống kê sự kiện theo enterprise_id trực tiếp
        $eventStats = TraceEvent::where('enterprise_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
            ")
            ->first();

        $scanStats = QrScanLog::where('enterprise_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN decision = 'allowed' THEN 1 ELSE 0 END) as allowed,
                SUM(CASE WHEN decision = 'blocked' THEN 1 ELSE 0 END) as blocked,
                SUM(CASE WHEN decision = 'expired' THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN decision = 'invalid' THEN 1 ELSE 0 END) as invalid
            ")
            ->first();

        // ── Scan theo ngày — 14 ngày gần nhất ────────────────────
        $scanByDay = QrScanLog::where('enterprise_id', $tenantId)
            ->where('scanned_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw("DATE(scanned_at) as day, COUNT(*) as count, SUM(CASE WHEN decision = 'allowed' THEN 1 ELSE 0 END) as allowed")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $scanTrend = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $row = $scanByDay->get($day);
            $scanTrend[] = [
                'day'     => $day,
                'label'   => now()->subDays($i)->format('d/m'),
                'count'   => $row ? (int) $row->count : 0,
                'allowed' => $row ? (int) $row->allowed : 0,
            ];
        }

        // ── Tiến độ 5 lô gần nhất ────────────────────────────────
        $recentBatches = Batch::with('product:id,name')
            ->where('enterprise_id', $tenantId)
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'code', 'product_id', 'product_name', 'status', 'created_at']);

        // FIX: Đếm số sự kiện qua bảng trung gian event_input_batches (lô hàng là đầu vào của sự kiện)
        $batchIds = $recentBatches->pluck('id');
        $eventCounts = DB::table('event_input_batches')
            ->join('trace_events', 'event_input_batches.trace_event_id', '=', 'trace_events.id')
            ->whereIn('event_input_batches.batch_id', $batchIds)
            ->select(
                'event_input_batches.batch_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN trace_events.status = 'published' THEN 1 ELSE 0 END) as published")
            )
            ->groupBy('event_input_batches.batch_id')
            ->get()
            ->keyBy('batch_id');

        $batchProgress = $recentBatches->map(function (Batch $batch) use ($eventCounts) {
            $ev = $eventCounts->get($batch->id);
            return [
                'id'           => $batch->id,
                'code'         => $batch->code,
                'product_name' => $batch->product?->name ?? $batch->product_name,
                'status'       => $batch->status,
                'published'    => $ev ? (int) $ev->published : 0,
                'total'        => $ev ? (int) $ev->total : 0,
                'created_at'   => $batch->created_at?->format('d/m/Y'),
            ];
        });

        // ── Lô bị thu hồi gần nhất ───────────────────────────────
        $recallRows = BatchRecall::with('batch:id,code')
            ->whereHas('batch', fn($q) => $q->where('enterprise_id', $tenantId))
            ->orderByDesc('recalled_at')
            ->limit(5)
            ->get(['id', 'batch_id', 'recalled_by', 'reason', 'status', 'recalled_at']);

        $recallerIds = $recallRows->pluck('recalled_by')->filter()->unique();
        $recallerMap = User::whereIn('id', $recallerIds)->pluck('name', 'id');

        $recentRecalls = $recallRows->map(fn($r) => [
            'id'          => $r->id,
            'batch_code'  => $r->batch?->code,
            'reason'      => $r->reason,
            'status'      => $r->status,
            'recalled_by' => $recallerMap->get($r->recalled_by) ?? '—',
            'recalled_at' => $r->recalled_at?->format('d/m/Y H:i'),
        ]);

        // ── Scan log gần nhất ─────────────────────────────────────
        $recentScans = QrScanLog::where('enterprise_id', $tenantId)
            ->orderByDesc('scanned_at')
            ->limit(10)
            ->get(['id', 'qr_type', 'decision', 'reason', 'scanned_at', 'ip', 'expected_place_name', 'distance_m'])
            ->map(fn($s) => [
                'id'         => $s->id,
                'qr_type'    => $s->qr_type,
                'decision'   => $s->decision,
                'reason'     => $s->reason,
                'place'      => $s->expected_place_name,
                'distance_m' => $s->distance_m,
                'ip'         => $s->ip,
                'scanned_at' => $s->scanned_at?->format('d/m H:i'),
            ]);

        // ── Top lô được scan nhiều nhất ──────────────────────────
        $topScanRows = QrScanLog::where('enterprise_id', $tenantId)
            ->whereNotNull('batch_id')
            ->where('decision', 'allowed')
            ->select('batch_id', DB::raw('COUNT(*) as scan_count'))
            ->groupBy('batch_id')
            ->orderByDesc('scan_count')
            ->limit(5)
            ->get();

        $topBatchIds = $topScanRows->pluck('batch_id');
        $topBatchMap = Batch::whereIn('id', $topBatchIds)
            ->get(['id', 'code', 'product_name'])
            ->keyBy('id');

        $topBatches = $topScanRows->map(fn($r) => [
            'batch_code'   => $topBatchMap->get($r->batch_id)?->code ?? "#{$r->batch_id}",
            'product_name' => $topBatchMap->get($r->batch_id)?->product_name ?? '—',
            'scan_count'   => (int) $r->scan_count,
        ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'products' => $totalProducts,
                'locations_count' => $totalLocations,
                'batches'  => [
                    'total'    => (int) ($batchStats->total ?? 0),
                    'active'   => (int) ($batchStats->active ?? 0),
                    'recalled' => (int) ($batchStats->recalled ?? 0),
                    'inactive' => (int) ($batchStats->inactive ?? 0),
                ],
                'events' => [
                    'total'     => (int) ($eventStats->total ?? 0),
                    'published' => (int) ($eventStats->published ?? 0),
                    'draft'     => (int) ($eventStats->draft ?? 0),
                ],
                'scans' => [
                    'total'   => (int) ($scanStats->total ?? 0),
                    'allowed' => (int) ($scanStats->allowed ?? 0),
                    'blocked' => (int) ($scanStats->blocked ?? 0),
                    'expired' => (int) ($scanStats->expired ?? 0),
                    'invalid' => (int) ($scanStats->invalid ?? 0),
                ],
            ],
            'scanTrend'     => $scanTrend,
            'batchProgress' => $batchProgress,
            'recentRecalls' => $recentRecalls,
            'recentScans'   => $recentScans,
            'topBatches'    => $topBatches,
        ]);
    }
}
