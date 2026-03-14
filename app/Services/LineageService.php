<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Enterprise;
use App\Models\TraceEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LineageService
{
    // ════════════════════════════════════════════════════════════════
    // 1. collectAllBatchIds — BFS theo cây lineage mới
    // ════════════════════════════════════════════════════════════════

    /**
     * Thu thập tất cả batch IDs trong cây lineage via:
     *   batch → origin_event_id → event_input_batches → batch cha → ...
     *
     * BFS để tránh stack overflow, visited set để tránh cycle.
     */
    public function collectAllBatchIds(int $batchId, int $maxDepth = 15): array
    {
        $visited = [];   // tập đã duyệt (batch IDs)
        $queue   = [['id' => $batchId, 'depth' => 0]];

        while (!empty($queue)) {
            $item  = array_shift($queue);
            $curId = $item['id'];
            $depth = $item['depth'];

            if (in_array($curId, $visited, true) || $depth > $maxDepth) {
                continue;
            }

            $visited[] = $curId;

            // Tìm origin_event của batch này
            $batch = Batch::withoutGlobalScopes()
                ->select('id', 'origin_event_id')
                ->find($curId);

            if (!$batch || !$batch->origin_event_id) {
                // Không có cha → đây là lô gốc (raw import từ ngoài / lô ban đầu)
                continue;
            }

            // Lấy tất cả input_batches của origin_event (các lô cha của batch này)
            $parentIds = DB::table('event_input_batches')
                ->where('trace_event_id', $batch->origin_event_id)
                ->pluck('batch_id')
                ->all();

            foreach ($parentIds as $parentId) {
                if (!in_array($parentId, $visited, true)) {
                    $queue[] = ['id' => (int) $parentId, 'depth' => $depth + 1];
                }
            }
        }

        return $visited;
    }

    // ════════════════════════════════════════════════════════════════
    // 2. buildLineageGraph — nodes + edges cho visualization
    // ════════════════════════════════════════════════════════════════

    /**
     * Xây dựng đồ thị lineage dạng {nodes, edges} phục vụ Batches/Lineage.vue.
     *
     * Node = batch, Edge = sự kiện transformation/transfer nối 2 batch.
     */
    public function buildLineageGraph(int $batchId): array
    {
        $allIds = $this->collectAllBatchIds($batchId);

        if (empty($allIds)) {
            return ['nodes' => [], 'edges' => []];
        }

        // Load tất cả batches
        $batches = Batch::withoutGlobalScopes()
            ->with('enterprise:id,name,code')
            ->withCount([
                'events as event_count',
            ])
            ->whereIn('id', $allIds)
            ->get();

        // Load tất cả events liên quan (input → output)
        // Dùng event_input_batches + event_output_batches để xây edges
        $inputLinks = DB::table('event_input_batches as eib')
            ->join('event_output_batches as eob', 'eib.trace_event_id', '=', 'eob.trace_event_id')
            ->join('trace_events as te', 'te.id', '=', 'eib.trace_event_id')
            ->whereIn('eib.batch_id', $allIds)
            ->whereIn('eob.batch_id', $allIds)
            ->select(
                'eib.batch_id as from_batch_id',
                'eob.batch_id as to_batch_id',
                'te.id as event_id',
                'te.cte_code',
                'te.event_category',
                'te.event_code',
            )
            ->get();

        // Build nodes
        $nodes = $batches->map(fn(Batch $b) => [
            'id'           => $b->id,
            'code'         => $b->code,
            'product_name' => $b->product_name,
            'enterprise'   => $b->enterprise ? [
                'id'   => $b->enterprise->id,
                'name' => $b->enterprise->name,
                'code' => $b->enterprise->code,
            ] : null,
            'batch_type'   => $b->batch_type,
            'status'       => $b->status,
            'event_count'  => (int) $b->event_count,
            'is_root'      => $b->id === $batchId,
        ])->values()->all();

        // Build edges (deduplicate theo event_id + from + to)
        $edgeKeys = [];
        $edges    = [];
        foreach ($inputLinks as $link) {
            $key = "{$link->from_batch_id}-{$link->to_batch_id}-{$link->event_id}";
            if (isset($edgeKeys[$key])) continue;
            $edgeKeys[$key] = true;

            $edges[] = [
                'from'     => $link->from_batch_id,
                'to'       => $link->to_batch_id,
                'type'     => $link->event_category,
                'label'    => $link->cte_code,
                'event_id' => $link->event_id,
            ];
        }

        return compact('nodes', 'edges');
    }

    // ════════════════════════════════════════════════════════════════
    // 3. loadPublishedEvents — load cross-tenant events
    // ════════════════════════════════════════════════════════════════

    /**
     * Load tất cả published TraceEvents của toàn bộ cây lineage.
     *
     * withoutGlobalScopes() bắt buộc vì lineage có thể cross-tenant.
     * Cache 5 phút nếu không có event nào còn draft.
     */
    public function loadPublishedEvents(int $batchId): Collection
    {
        $cacheKey = "lineage_events_{$batchId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($batchId) {
            $allIds = $this->collectAllBatchIds($batchId);

            if (empty($allIds)) {
                return collect();
            }

            return TraceEvent::withoutGlobalScopes()
                ->with([
                    'inputBatches'                     => fn($q) => $q->withoutGlobalScopes(),
                    'inputBatches.enterprise:id,name,code,gln',
                    'outputBatches'                    => fn($q) => $q->withoutGlobalScopes(),
                    'traceLocation:id,name,address_detail,province,gln,ai_type,lat,lng',
                    'eventCertificates',
                    'eventCertificates.certificate:id,name,organization',
                    'enterprise:id,name,code',
                ])
                ->where('status', 'published')
                ->whereHas('inputBatches', fn($q) => $q->withoutGlobalScopes()->whereIn('batches.id', $allIds))
                ->orderBy('event_time', 'asc')
                ->get();
        });
    }

    // ════════════════════════════════════════════════════════════════
    // 4. formatEventForPublic — format cho Trace/Show.vue
    // ════════════════════════════════════════════════════════════════

    /**
     * Format một TraceEvent để hiển thị trên trang public scan.
     * Compatible với Trace/Show.vue.
     */
    public function formatEventForPublic(TraceEvent $event): array
    {
        // Enterprise từ input batch đầu tiên (hoặc từ event trực tiếp)
        $primaryBatch      = $event->inputBatches->first();
        $enterprise        = $primaryBatch?->enterprise ?? $event->enterprise;
        $loc               = $event->traceLocation;

        // Certificates
        $certificates = $event->eventCertificates->map(fn($ec) => [
            'name'         => $ec->certificate?->name ?? 'Chứng chỉ tùy chỉnh',
            'organization' => $ec->certificate?->organization,
            'result'       => $ec->result,
            'reference_no' => $ec->reference_no,
            'issued_date'  => $ec->issued_date?->format('d/m/Y'),
            'expiry_date'  => $ec->expiry_date?->format('d/m/Y'),
            'is_expired'   => $ec->isExpired(),
        ])->values()->all();

        return [
            'id'            => $event->id,
            'event_code'    => $event->event_code,
            'cte_code'      => $event->cte_code,
            'event_category'=> $event->event_category,
            'event_time'    => optional($event->event_time)->format('d/m/Y H:i'),
            'event_time_iso'=> optional($event->event_time)->toIso8601String(),
            'who_name'      => $event->who_name,
            'why_reason'    => $event->why_reason,
            'kde_data'      => $event->kde_data ?? [],
            'attachments'   => $event->attachments ?? [],
            'note'          => $event->note,

            // WHERE — location (GS1 GLN + địa chỉ)
            'location' => $loc ? [
                'name'     => $loc->name,
                'ai_type'  => $loc->ai_type,
                'gln'      => $loc->gln,
                'address'  => $loc->address_detail,
                'province' => $loc->province,
                'lat'      => $loc->lat,
                'lng'      => $loc->lng,
            ] : null,

            // WHO — enterprise
            'enterprise' => $enterprise ? [
                'name' => $enterprise->name,
                'code' => $enterprise->code,
            ] : null,

            // Blockchain proof
            'ipfs_cid'     => $event->ipfs_cid,
            'ipfs_url'     => $event->ipfs_url ?? null,
            'content_hash' => $event->content_hash,
            'tx_hash'      => $event->tx_hash,
            'published_at' => optional($event->published_at)->format('d/m/Y H:i'),

            // Certificates
            'certificates' => $certificates,

            // Input/output batches for context (không expose full detail)
            'input_batch_codes'  => $event->inputBatches->pluck('code')->all(),
            'output_batch_codes' => $event->outputBatches->pluck('code')->all(),
        ];
    }
}
