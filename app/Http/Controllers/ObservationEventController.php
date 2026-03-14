<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\EventCertificate;
use App\Models\TraceEvent;
use App\Models\TraceLocation;
use App\Traits\HasTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ObservationEventController extends Controller
{
    use HasTenant;

    // ── create ────────────────────────────────────────────
    public function create(\Illuminate\Http\Request $request): \Inertia\Response
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        return \Inertia\Inertia::render('Events/CreateObservation', [
            'batches' => \App\Models\Batch::where('enterprise_id', $tenantId)
                ->whereIn('status', ['active', 'completed'])
                ->with('enterprise:id,name')
                ->get(['id', 'code', 'enterprise_id', 'product_name', 'current_quantity', 'unit', 'status']),

            'locations' => \App\Models\TraceLocation::where('enterprise_id', $tenantId)
                ->get(['id', 'name', 'gln', 'ai_type', 'address_detail', 'province']),

            'certificates' => \App\Models\Certificate::where('enterprise_id', $tenantId)
                ->where('status', 'active')
                ->get(['id', 'name', 'organization']),

            'cte_options' => [],   // frontend dùng DEFAULT_CTE
        ]);
    }

    // ── store ─────────────────────────────────────────────


    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.create']), 403);

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'cte_code'                       => 'required|string|max:60',
            'event_time'                     => 'required|date',
            'who_name'                       => 'required|string|max:255',
            'trace_location_id'              => 'nullable|exists:trace_locations,id',
            'kde_data'                       => 'nullable|array',
            'note'                           => 'nullable|string|max:2000',
            'input_batch_ids'                => 'required|array|min:1',
            'input_batch_ids.*'              => 'integer|exists:batches,id',
            'why_reason'                     => 'nullable|string|max:500',
            'has_certification'              => 'nullable|boolean',
            'certification.certificate_id'   => 'required_if:has_certification,true|nullable|integer|exists:certificates,id',
            'certification.result'           => 'required_if:has_certification,true|nullable|in:pass,fail,conditional',
            'certification.reference_no'     => 'nullable|string|max:100',
            'certification.issued_date'      => 'nullable|date',
            'certification.expiry_date'      => 'nullable|date|after:certification.issued_date',
        ]);

        // Validate trace_location thuộc tenant
        if (!empty($data['trace_location_id'])) {
            $loc = TraceLocation::find($data['trace_location_id']);
            abort_unless($loc && (int) $loc->enterprise_id === $tenantId, 403, 'Địa điểm không thuộc doanh nghiệp của bạn.');
        }

        // Validate tất cả batch thuộc tenant
        $batches = Batch::whereIn('id', $data['input_batch_ids'])
            ->where('enterprise_id', $tenantId)
            ->get();

        if ($batches->count() !== count($data['input_batch_ids'])) {
            return back()->withErrors(['input_batch_ids' => 'Một số lô không hợp lệ hoặc không thuộc doanh nghiệp.']);
        }

        DB::transaction(function () use ($data, $tenantId, $batches) {
            // 1. Sinh event_code tự động: EVT-{CTE_UPPER}-{YYYYMM}-{SEQ3}
            $eventCode = $this->generateEventCode($tenantId, $data['cte_code']);

            // 2. Tạo TraceEvent
            $event = TraceEvent::create([
                'enterprise_id'    => $tenantId,
                'event_category'   => TraceEvent::CAT_OBSERVATION,
                'event_code'       => $eventCode,
                'event_token'      => (string) Str::uuid(),
                'cte_code'         => $data['cte_code'],
                'event_type'       => $data['cte_code'], // legacy compat
                'event_time'       => $data['event_time'],
                'trace_location_id'=> $data['trace_location_id'] ?? null,
                'who_name'         => $data['who_name'],
                'why_reason'       => $data['why_reason'] ?? null,
                'kde_data'         => $data['kde_data'] ?? null,
                'note'             => $data['note'] ?? null,
                'status'           => 'draft',
            ]);

            // 3. Gắn input batches vào pivot (observation không sinh lô mới, không đổi status)
            foreach ($batches as $batch) {
                $event->inputBatches()->attach($batch->id, [
                    'quantity' => $batch->current_quantity ?? $batch->quantity,
                    'unit'     => $batch->unit,
                ]);
            }

            // 4. Tạo EventCertificate nếu has_certification = true
            if (!empty($data['has_certification']) && !empty($data['certification'])) {
                $cert = $data['certification'];
                foreach ($batches as $batch) {
                    EventCertificate::create([
                        'trace_event_id' => $event->id,
                        'batch_id'       => $batch->id,
                        'certificate_id' => $cert['certificate_id'],
                        'result'         => $cert['result'],
                        'reference_no'   => $cert['reference_no'] ?? null,
                        'issued_date'    => $cert['issued_date'] ?? null,
                        'expiry_date'    => $cert['expiry_date'] ?? null,
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Đã ghi nhận sự kiện quan sát thành công.');
    }

    // ── update ────────────────────────────────────────────

    public function update(Request $request, TraceEvent $event): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);
        $this->assertTenant($request, $event);

        if ($event->status !== 'draft') {
            return back()->withErrors(['error' => 'Chỉ có thể chỉnh sửa sự kiện ở trạng thái draft.']);
        }

        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'cte_code'                       => 'required|string|max:60',
            'event_time'                     => 'required|date',
            'who_name'                       => 'required|string|max:255',
            'trace_location_id'              => 'nullable|exists:trace_locations,id',
            'kde_data'                       => 'nullable|array',
            'note'                           => 'nullable|string|max:2000',
            'input_batch_ids'                => 'required|array|min:1',
            'input_batch_ids.*'              => 'integer|exists:batches,id',
            'why_reason'                     => 'nullable|string|max:500',
            'has_certification'              => 'nullable|boolean',
            'certification.certificate_id'   => 'required_if:has_certification,true|nullable|integer|exists:certificates,id',
            'certification.result'           => 'required_if:has_certification,true|nullable|in:pass,fail,conditional',
            'certification.reference_no'     => 'nullable|string|max:100',
            'certification.issued_date'      => 'nullable|date',
            'certification.expiry_date'      => 'nullable|date|after:certification.issued_date',
        ]);

        // Validate trace_location thuộc tenant
        if (!empty($data['trace_location_id'])) {
            $loc = TraceLocation::find($data['trace_location_id']);
            abort_unless($loc && (int) $loc->enterprise_id === $tenantId, 403, 'Địa điểm không thuộc doanh nghiệp của bạn.');
        }

        // Validate batch thuộc tenant
        $batches = Batch::whereIn('id', $data['input_batch_ids'])
            ->where('enterprise_id', $tenantId)
            ->get();

        if ($batches->count() !== count($data['input_batch_ids'])) {
            return back()->withErrors(['input_batch_ids' => 'Một số lô không hợp lệ hoặc không thuộc doanh nghiệp.']);
        }

        DB::transaction(function () use ($data, $event, $batches) {
            // 1. Cập nhật thông tin event
            $event->update([
                'cte_code'         => $data['cte_code'],
                'event_type'       => $data['cte_code'],
                'event_time'       => $data['event_time'],
                'trace_location_id'=> $data['trace_location_id'] ?? null,
                'who_name'         => $data['who_name'],
                'why_reason'       => $data['why_reason'] ?? null,
                'kde_data'         => $data['kde_data'] ?? null,
                'note'             => $data['note'] ?? null,
            ]);

            // 2. Sync lại input_batch_ids qua pivot
            $pivotData = [];
            foreach ($batches as $batch) {
                $pivotData[$batch->id] = [
                    'quantity' => $batch->current_quantity ?? $batch->quantity,
                    'unit'     => $batch->unit,
                ];
            }
            $event->inputBatches()->sync($pivotData);

            // 3. Xóa EventCertificate cũ rồi tạo lại nếu có
            EventCertificate::where('trace_event_id', $event->id)->delete();

            if (!empty($data['has_certification']) && !empty($data['certification'])) {
                $cert = $data['certification'];
                foreach ($batches as $batch) {
                    EventCertificate::create([
                        'trace_event_id' => $event->id,
                        'batch_id'       => $batch->id,
                        'certificate_id' => $cert['certificate_id'],
                        'result'         => $cert['result'],
                        'reference_no'   => $cert['reference_no'] ?? null,
                        'issued_date'    => $cert['issued_date'] ?? null,
                        'expiry_date'    => $cert['expiry_date'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('success', 'Đã cập nhật sự kiện quan sát.');
    }

    // ── destroy ───────────────────────────────────────────

    public function destroy(Request $request, TraceEvent $event): RedirectResponse
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.trace_events.manage']), 403);
        $this->assertTenant($request, $event);

        if ($event->status !== 'draft') {
            return back()->withErrors(['error' => 'Chỉ có thể xóa sự kiện ở trạng thái draft.']);
        }

        DB::transaction(function () use ($event) {
            // 1. Detach input batches (pivot)
            $event->inputBatches()->detach();

            // 2. Xóa EventCertificate liên quan
            EventCertificate::where('trace_event_id', $event->id)->delete();

            // 3. Xóa event
            $event->delete();
        });

        return back()->with('success', 'Đã xóa sự kiện quan sát.');
    }

    // ── Private helpers ───────────────────────────────────

    /**
     * Sinh event_code: EVT-{ENT_SHORT}-{CTE_7}-{YYYYMM}-{SEQ3}
     * Query MAX seq trong tháng hiện tại để tự tăng.
     */
    private function generateEventCode(int $tenantId, string $cteCode): string
    {
        $yearMonth = now()->format('Ym');
        $cteUpper  = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $cteCode), 0, 7));
        
        $enterpriseCode = DB::table('enterprises')->where('id', $tenantId)->value('code') ?? 'UNK';
        $prefix    = "EVT-{$enterpriseCode}-{$cteUpper}-{$yearMonth}-";

        // Tìm số thứ tự lớn nhất trong tháng theo prefix
        $last = DB::table('trace_events')
            ->where('enterprise_id', $tenantId)
            ->where('event_code', 'like', $prefix . '%')
            ->orderByDesc('event_code')
            ->value('event_code');

        $seq = 1;
        if ($last) {
            $lastSeq = (int) substr($last, strrpos($last, '-') + 1);
            $seq = $lastSeq + 1;
        }

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
