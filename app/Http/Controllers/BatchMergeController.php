<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\TraceEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchMergeController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }

    public function show(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = $this->tenantId($request);

        $batches = Batch::where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->with('product:id,name')
            ->get(['id', 'code', 'product_name', 'product_id',
                   'current_quantity', 'quantity', 'unit', 'certifications']);

        return Inertia::render('Batches/Merge', [
            'availableBatches' => $batches->map(fn($b) => [
                'id'               => $b->id,
                'code'             => $b->code,
                'product_name'     => $b->product?->name ?? $b->product_name,
                // current_quantity fallback về quantity nếu null
                'current_quantity' => $b->current_quantity ?? $b->quantity ?? 0,
                'unit'             => $b->unit ?? '',
                'certifications'   => $b->certifications ?? [],
            ]),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = $this->tenantId($request);

        $data = $request->validate([
            'input_batch_ids'       => 'required|array|min:2',
            'input_batch_ids.*'     => 'required|integer|exists:batches,id',
            'output_product_name'   => 'required|string|max:255',
        ]);

        // Load tất cả lô đầu vào
        $inputBatches = Batch::whereIn('id', $data['input_batch_ids'])
            ->where('enterprise_id', $tenantId)
            ->where('status', 'active')
            ->get();

        if ($inputBatches->count() !== count($data['input_batch_ids'])) {
            return back()->withErrors(['input_batch_ids' => 'Một số lô không hợp lệ hoặc không thuộc doanh nghiệp.']);
        }

        // Validate cùng đơn vị
        $units = $inputBatches->pluck('unit')->filter()->unique();
        if ($units->count() > 1) {
            return back()->withErrors([
                'input_batch_ids' => 'Các lô phải cùng đơn vị tính. Hiện tại: ' . $units->join(', '),
            ]);
        }

        // Tính tổng số lượng server-side — không tin frontend
        $totalQty  = $inputBatches->sum(fn($b) => $b->current_quantity ?? $b->quantity ?? 0);
        $unit      = $units->first() ?? '';

        // Chứng chỉ chung: giao của tất cả lô
        $allCerts = $inputBatches->map(fn($b) => $b->certifications ?? []);
        $commonCerts = $allCerts->reduce(fn($carry, $certs) =>
            $carry === null ? $certs : array_values(array_intersect($carry, $certs))
        );

        DB::transaction(function () use ($data, $inputBatches, $totalQty, $unit, $commonCerts, $tenantId) {
            $firstCode = $inputBatches->first()->code;
            $seq       = str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
            $newCode   = $firstCode . '-M' . $seq;

            $outputBatch = Batch::create([
                'enterprise_id'        => $tenantId,
                'origin_enterprise_id' => $tenantId,
                'product_id'           => $inputBatches->first()->product_id, // ← Thêm dòng này
                'product_name'         => $data['output_product_name'],
                'code'                 => $newCode,
                'batch_type'           => 'merged',
                'quantity'             => $totalQty,
                'current_quantity'     => $totalQty,
                'unit'                 => $unit,
                'certifications'       => $commonCerts ?? [],
                'status'               => 'active',
            ]);

            $event = TraceEvent::create([
                'batch_id'      => $outputBatch->id,
                'enterprise_id' => $tenantId,
                'cte_code'      => 'merge',
                'event_type'    => 'merge',  
                'kde_data'      => [
                    'action'        => 'merge',
                    'input_batches' => $inputBatches->map(fn($b) => [
                        'code'     => $b->code,
                        'quantity' => $b->current_quantity ?? $b->quantity ?? 0,
                        'unit'     => $b->unit,
                    ]),
                ],
                'who_name'   => auth()->user()->name,
                'status'     => 'draft',
                'event_time' => now(),
            ]);

            foreach ($inputBatches as $inputBatch) {
                BatchLineage::create([
                    'transformation_type' => 'merge',
                    'input_batch_id'      => $inputBatch->id,
                    'output_batch_id'     => $outputBatch->id,
                    'quantity'            => $inputBatch->current_quantity ?? $inputBatch->quantity ?? 0,
                    'unit'                => $inputBatch->unit,
                    'event_id'            => $event->id,
                ]);

                $inputBatch->update(['current_quantity' => 0, 'status' => 'consumed']);
            }
        });

        return redirect()->route('batches.index')
            ->with('success', 'Đã gộp lô thành công.');
    }
}