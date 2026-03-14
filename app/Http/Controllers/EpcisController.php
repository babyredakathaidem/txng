<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\TraceEvent;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EpcisController — Export dữ liệu chuẩn EPCIS 2.0 JSON-LD
 *
 * Chuẩn áp dụng:
 *   - EPCIS 2.0 (GS1 Standard, released 2022)
 *   - JSON-LD binding: https://ref.gs1.org/standards/epcis/
 *
 * Mục đích:
 *   Liên thông dữ liệu với Cổng truy xuất nguồn gốc Quốc gia (tracability.gov.vn)
 *   và các hệ thống GS1 quốc tế.
 *
 * Endpoint: GET /api/v1/epcis/events/{batch_id}
 *
 * Response format: application/ld+json
 */
class EpcisController extends Controller
{
    /**
     * Mapping từ CTE code nội bộ → GS1 EPCIS bizStep URI
     *
     * Chuẩn: GS1 CBV (Core Business Vocabulary) 2.0
     * https://ref.gs1.org/cbv/BizStep
     */
    private const BIZ_STEP_MAP = [
        // Trồng trọt / Chăn nuôi
        'planting'       => 'https://ref.gs1.org/cbv/BizStep-other',
        'growing'        => 'https://ref.gs1.org/cbv/BizStep-other',
        'harvest'        => 'https://ref.gs1.org/cbv/BizStep-harvesting',
        'harvesting'     => 'https://ref.gs1.org/cbv/BizStep-harvesting',

        // Chế biến / Sản xuất
        'processing'     => 'https://ref.gs1.org/cbv/BizStep-commissioning',
        'transformation' => 'https://ref.gs1.org/cbv/BizStep-commissioning',
        'manufacturing'  => 'https://ref.gs1.org/cbv/BizStep-commissioning',

        // Đóng gói
        'packaging'      => 'https://ref.gs1.org/cbv/BizStep-packing',
        'packing'        => 'https://ref.gs1.org/cbv/BizStep-packing',
        'labeling'       => 'https://ref.gs1.org/cbv/BizStep-accepting',

        // Lưu kho
        'storage'        => 'https://ref.gs1.org/cbv/BizStep-storing',
        'storing'        => 'https://ref.gs1.org/cbv/BizStep-storing',
        'warehousing'    => 'https://ref.gs1.org/cbv/BizStep-storing',

        // Vận chuyển
        'transport'      => 'https://ref.gs1.org/cbv/BizStep-shipping',
        'shipping'       => 'https://ref.gs1.org/cbv/BizStep-shipping',
        'loading'        => 'https://ref.gs1.org/cbv/BizStep-loading',
        'receiving'      => 'https://ref.gs1.org/cbv/BizStep-receiving',

        // Kiểm tra / Chất lượng
        'inspection'     => 'https://ref.gs1.org/cbv/BizStep-inspecting',
        'quality_check'  => 'https://ref.gs1.org/cbv/BizStep-inspecting',
        'testing'        => 'https://ref.gs1.org/cbv/BizStep-inspecting',

        // Chuyển giao
        'transfer_sent'     => 'https://ref.gs1.org/cbv/BizStep-shipping',
        'transfer_received' => 'https://ref.gs1.org/cbv/BizStep-receiving',

        // Gộp / Tách
        'merge'  => 'https://ref.gs1.org/cbv/BizStep-commissioning',
        'split'  => 'https://ref.gs1.org/cbv/BizStep-decommissioning',

        // Phân phối / Bán lẻ
        'distribution' => 'https://ref.gs1.org/cbv/BizStep-dispatching',
        'retail'       => 'https://ref.gs1.org/cbv/BizStep-selling',

        // Mặc định
        'custom' => 'https://ref.gs1.org/cbv/BizStep-other',
    ];

    /**
     * Mapping EPCIS event type theo CTE
     * ObjectEvent | AggregationEvent | TransformationEvent | AssociationEvent
     */
    private const EVENT_TYPE_MAP = [
        'merge'  => 'TransformationEvent',
        'split'  => 'TransformationEvent',
        'default'=> 'ObjectEvent',
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function events(Request $request, int $batchId): JsonResponse
    {
        // Tìm batch — không dùng TenantScope vì API public (chỉ published events)
        $batch = Batch::with([
            'product:id,name,gtin,enterprise_id',
            'enterprise:id,name,code,gln,province',
        ])->findOrFail($batchId);

        // Thu thập tất cả batch IDs qua lineage (giống QrScanController@loadEvents)
        $allBatchIds = [];
        $this->collectAncestorBatchIds($batch->id, $allBatchIds, 0);

        // Load toàn bộ published events theo lineage
        $events = TraceEvent::with([
            'inputBatches:id,code,product_id,enterprise_id',
            'inputBatches.product:id,name,gtin',
            'inputBatches.enterprise:id,name,code,gln,province',
        ])
            ->whereHas('inputBatches', fn($q) => $q->whereIn('batches.id', $allBatchIds))
            ->where('status', 'published')
            ->orderBy('event_time')
            ->orderBy('id')
            ->get();

        // Build EPCIS 2.0 JSON-LD document
        $document = $this->buildEpcisDocument($batch, $events);

        return response()->json($document, 200, [
            'Content-Type' => 'application/ld+json',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build EPCIS 2.0 document
    // ─────────────────────────────────────────────────────────────────────────

    private function buildEpcisDocument(Batch $batch, $events): array
    {
        $qrSvc = app(QrCodeService::class);
        $gtin  = $qrSvc->resolveGtin($batch);

        return [
            // JSON-LD context — EPCIS 2.0
            '@context' => [
                'https://ref.gs1.org/standards/epcis/2.0.0/epcis-context.jsonld',
                [
                    // Extension namespace cho trường tùy chỉnh AGU
                    'agu' => 'https://traceability.agu.edu.vn/ns#',
                ],
            ],

            // EPCIS Document envelope
            'type'               => 'EPCISDocument',
            'schemaVersion'      => '2.0',
            'creationDate'       => now()->toIso8601String(),
            'epcisHeader'        => $this->buildHeader($batch, $gtin),
            'epcisBody'          => [
                'eventList' => $events->map(fn($e) => $this->convertToEpcisEvent($e, $gtin))->values()->toArray(),
            ],
        ];
    }

    private function buildHeader(Batch $batch, string $gtin): array
    {
        $gln = $batch->enterprise?->gln ?? 'urn:epc:id:sgln:0000000.00000.0';

        return [
            'epcisMasterData' => [
                'VocabularyList' => [
                    [
                        'type'           => 'urn:epcglobal:epcis:vtype:BusinessLocation',
                        'VocabularyElementList' => [
                            [
                                'id'         => $this->formatGln($gln),
                                'attribute'  => [
                                    ['id' => 'urn:epcglobal:cbv:mda#name',    '#text' => $batch->enterprise?->name],
                                    ['id' => 'urn:epcglobal:cbv:mda#city',    '#text' => $batch->enterprise?->province],
                                    ['id' => 'urn:epcglobal:cbv:mda#countryCode', '#text' => 'VN'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type'           => 'urn:epcglobal:epcis:vtype:EPCClass',
                        'VocabularyElementList' => [
                            [
                                'id'        => "urn:epc:idpat:sgtin:{$gtin}.*",
                                'attribute' => [
                                    ['id' => 'urn:epcglobal:cbv:mda#descriptionShort',
                                     '#text' => $batch->product?->name ?? $batch->product_name],
                                    ['id' => 'urn:epcglobal:cbv:mda#lotNumber',
                                     '#text' => $batch->code],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert một TraceEvent nội bộ → EPCIS 2.0 Event object
     *
     * EPCIS Event structure:
     *   eventTime           → WHEN (ISO 8601)
     *   eventTimeZoneOffset → Timezone offset VN = +07:00
     *   epcList             → WHAT (SGTIN URN)
     *   action              → ADD | OBSERVE | DELETE
     *   bizStep             → GS1 CBV BizStep URI
     *   disposition         → GS1 CBV Disposition URI
     *   readPoint           → WHERE (GLN URN)
     *   bizLocation         → WHERE (business location GLN)
     *   bizTransactionList  → WHY (invoice, PO, ...)
     *   sourceList          → FROM (transfer)
     *   destinationList     → TO (transfer)
     *   ilmd                → WHEN mới sản xuất (instance/lot master data)
     */
    private function convertToEpcisEvent(TraceEvent $event, string $gtin): array
    {
        $cteCode   = $event->cte_code ?? $event->event_type ?? 'custom';
        $bizStep   = self::BIZ_STEP_MAP[$cteCode] ?? self::BIZ_STEP_MAP['custom'];
        $eventType = self::EVENT_TYPE_MAP[$cteCode] ?? self::EVENT_TYPE_MAP['default'];

        // SGTIN = urn:epc:id:sgtin:{company_prefix}.{item_ref}.{serial}
        // Với lot-based system → dùng LGTIN (lot) thay vì serial
        $sgtin = $this->buildSgtin($gtin, $event->inputBatches->first()?->code ?? '');

        $epcisEvent = [
            'type'                  => $eventType,
            'eventID'               => "ni:///sha-256;{$event->content_hash}?ver=CBV2.0",
            'eventTime'             => optional($event->event_time)->toIso8601String(),
            'eventTimeZoneOffset'   => '+07:00',
            'epcList'               => [$sgtin],
            'action'                => $this->resolveAction($cteCode),
            'bizStep'               => $bizStep,
            'disposition'           => $this->resolveDisposition($cteCode),
            'readPoint'             => [
                'id' => $this->resolveReadPoint($event),
            ],
            'bizLocation'           => [
                'id' => $this->resolveReadPoint($event),
            ],
        ];

        // bizTransactionList — gắn invoice/WHY nếu có
        $bizTx = $this->buildBizTransactionList($event);
        if (! empty($bizTx)) {
            $epcisEvent['bizTransactionList'] = $bizTx;
        }

        // sourceList / destinationList cho transfer events
        if (in_array($cteCode, ['transfer_sent', 'transfer_received'])) {
            $epcisEvent = array_merge($epcisEvent, $this->buildTransferLists($event));
        }

        // ilmd (Instance/Lot Master Data) — chỉ cho commissioning events
        if (in_array($cteCode, ['processing', 'harvest', 'harvesting', 'transformation'])) {
            $epcisEvent['ilmd'] = $this->buildIlmd($event);
        }

        // Extension fields (AGU namespace) — trường tùy chỉnh không nằm trong chuẩn GS1
        $epcisEvent['agu:kdeData']     = $event->kde_data ?? [];
        $epcisEvent['agu:whoName']     = $event->who_name;
        $epcisEvent['agu:whereAddr']   = $event->where_address;
        $epcisEvent['agu:whyReason']   = $event->why_reason;
        $epcisEvent['agu:ipfsCid']     = $event->ipfs_cid;
        $epcisEvent['agu:fabricTxId']  = $event->tx_hash;
        $epcisEvent['agu:contentHash'] = $event->content_hash;
        $epcisEvent['agu:tcvnRef']     = 'TCVN 12850:2019';

        // GPS nếu có
        if ($event->where_lat && $event->where_lng) {
            $epcisEvent['agu:gps'] = [
                'lat' => $event->where_lat,
                'lng' => $event->where_lng,
            ];
        }

        // Attachments
        if (! empty($event->attachments)) {
            $epcisEvent['agu:attachments'] = $event->attachments;
        }

        return $epcisEvent;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper builders
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tạo LGTIN URN (Lot-based SGTIN)
     * Format: urn:epc:class:lgtin:{company_prefix}.{item_ref}.{lot}
     *
     * Theo GS1 EPC Tag Data Standard:
     *   GTIN-14 = 0 + company_prefix(7) + item_ref(5) + check(1)
     *   Ở đây ta split theo GS1 prefix Việt Nam 893XXXXX
     */
    private function buildSgtin(string $gtin14, string $lotCode): string
    {
        // GS1 Việt Nam company prefix = 893 + next 4-7 digits
        // Đơn giản hóa: company_prefix = 8 digits đầu, item_ref = 5 digits cuối (trừ check)
        $companyPrefix = substr($gtin14, 0, 8);  // 8 digits
        $itemRef       = substr($gtin14, 8, 5);  // 5 digits
        $lotNormalized = preg_replace('/[^A-Za-z0-9\-_.]/', '-', $lotCode);

        return "urn:epc:class:lgtin:{$companyPrefix}.{$itemRef}.{$lotNormalized}";
    }

    /**
     * EPCIS action:
     * - ADD    → sự kiện tạo ra hàng hóa (commissioning, harvest)
     * - OBSERVE → sự kiện quan sát (storage, transport, inspection)
     * - DELETE → sự kiện tiêu thụ/hủy (consuming, decommissioning)
     */
    private function resolveAction(string $cteCode): string
    {
        $addCodes = ['harvest', 'harvesting', 'processing', 'transformation', 'manufacturing'];
        $delCodes = ['consuming', 'recall'];

        if (in_array($cteCode, $addCodes)) return 'ADD';
        if (in_array($cteCode, $delCodes)) return 'DELETE';
        return 'OBSERVE';
    }

    /**
     * EPCIS disposition (trạng thái hàng hóa tại thời điểm sự kiện)
     * GS1 CBV Disposition: https://ref.gs1.org/cbv/Disp
     */
    private function resolveDisposition(string $cteCode): string
    {
        return match($cteCode) {
            'harvest', 'harvesting', 'processing', 'transformation'
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
            'packaging', 'packing'
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
            'storage', 'storing'
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
            'transfer_sent', 'shipping'
                => 'https://ref.gs1.org/cbv/Disp-in_transit',
            'transfer_received', 'receiving'
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
            'inspection', 'quality_check'
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
            'recall'
                => 'https://ref.gs1.org/cbv/Disp-recalled',
            default
                => 'https://ref.gs1.org/cbv/Disp-in_progress',
        };
    }

    /**
     * readPoint → GLN URN của doanh nghiệp/địa điểm
     * Fallback về GPS-based URN nếu không có GLN
     */
    private function resolveReadPoint(TraceEvent $event): string
    {
        $enterprise = $event->inputBatches->first()?->enterprise;
        $gln        = $enterprise?->gln;

        if ($gln) {
            return $this->formatGln($gln);
        }

        // Fallback: dùng GPS nếu có
        if ($event->where_lat && $event->where_lng) {
            return "geo:{$event->where_lat},{$event->where_lng}";
        }

        // Fallback: enterprise code làm URN nội bộ
        $code = $enterprise?->code ?? 'unknown';
        return "urn:agu:location:{$code}";
    }

    /**
     * Format GLN thành URN
     * GLN = 13 digits → urn:epc:id:sgln:{company_prefix}.{location_ref}.0
     */
    private function formatGln(string $gln): string
    {
        if (str_starts_with($gln, 'urn:')) {
            return $gln;
        }

        // GLN-13 → split thành company_prefix(9) + location_ref(3)
        $clean = preg_replace('/\D/', '', $gln);
        if (strlen($clean) >= 13) {
            $companyPrefix = substr($clean, 0, 9);
            $locationRef   = substr($clean, 9, 3);
            return "urn:epc:id:sgln:{$companyPrefix}.{$locationRef}.0";
        }

        return "urn:agu:sgln:{$clean}";
    }

    /**
     * bizTransactionList — gắn invoice/PO vào event
     * GS1 CBV BizTransaction Types: po, pedigree, prodorder, desadv, inv, ...
     */
    private function buildBizTransactionList(TraceEvent $event): array
    {
        $list = [];

        // Invoice number từ KDE
        $invoiceNo = $event->kde_data['invoice_no'] ?? null;
        if ($invoiceNo) {
            $list[] = [
                'type'             => 'https://ref.gs1.org/cbv/BTT-inv',
                'bizTransaction'   => "urn:epc:id:gdti:{$invoiceNo}",
            ];
        }

        // WHY / tiêu chuẩn → lưu dạng custom biz transaction
        if ($event->why_reason) {
            $list[] = [
                'type'           => 'urn:agu:cbv:BTT-standard',
                'bizTransaction' => $event->why_reason,
            ];
        }

        return $list;
    }

    /**
     * sourceList / destinationList cho transfer events
     * GS1 CBV Source/Destination Types: owning_party, possessing_party, location
     */
    private function buildTransferLists(TraceEvent $event): array
    {
        $kde        = $event->kde_data ?? [];
        $result     = [];
        $enterprise = $event->inputBatches->first()?->enterprise;
        $gln        = $enterprise?->gln;

        if (isset($kde['from_enterprise'])) {
            $result['sourceList'] = [[
                'type'   => 'https://ref.gs1.org/cbv/SDT-owning_party',
                'source' => "urn:agu:enterprise:{$kde['from_enterprise']}",
            ]];
        }

        if (isset($kde['to_enterprise'])) {
            $result['destinationList'] = [[
                'type'        => 'https://ref.gs1.org/cbv/SDT-owning_party',
                'destination' => "urn:agu:enterprise:{$kde['to_enterprise']}",
            ]];
        }

        return $result;
    }

    /**
     * ilmd — Instance/Lot Master Data
     * Chứa thông tin ngày sản xuất, hạn sử dụng theo chuẩn GS1
     */
    private function buildIlmd(TraceEvent $event): array
    {
        $batch = $event->inputBatches->first();
        $ilmd  = [];

        if ($batch?->production_date) {
            $ilmd['cbvmda:lotNumber'] = $batch->code;
            $ilmd['cbvmda:itemExpirationDate'] = optional($batch->expiry_date)->toDateString();
            $ilmd['cbvmda:productionDate']     = optional($batch->production_date)->toDateString();
        }

        if ($event->kde_data) {
            // Map thêm KDE vào ilmd nếu liên quan đến production
            foreach (['who_performer', 'what_output', 'what_quantity'] as $key) {
                if (isset($event->kde_data[$key])) {
                    $ilmd["agu:kde_{$key}"] = $event->kde_data[$key];
                }
            }
        }

        return $ilmd;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lineage traversal (giống QrScanController)
    // ─────────────────────────────────────────────────────────────────────────

    private function collectAncestorBatchIds(int $batchId, array &$ids, int $depth): void
    {
        if ($depth > 10 || in_array($batchId, $ids)) {
            return;
        }

        $ids[] = $batchId;

        $batch = Batch::select('id', 'parent_batch_id', 'batch_type')
            ->find($batchId);

        if (! $batch) {
            return;
        }

        // Split → đệ quy lên lô cha
        if ($batch->parent_batch_id) {
            $this->collectAncestorBatchIds($batch->parent_batch_id, $ids, $depth + 1);
        }

        // Merge → đệ quy lên tất cả input batches
        if ($batch->batch_type === 'merged') {
            $inputIds = BatchLineage::where('output_batch_id', $batchId)
                ->where('transformation_type', 'merge')
                ->pluck('input_batch_id');

            foreach ($inputIds as $inputId) {
                $this->collectAncestorBatchIds($inputId, $ids, $depth + 1);
            }
        }
    }
}