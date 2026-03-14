<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchLineage;
use App\Models\TraceEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BatchSplitController extends Controller
{
    private function tenantId(Request $request): int
    {
        return (int) $request->user()->enterprise_id;
    }
    public function show(Request $request, Batch $batch)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless($batch->status === 'active', 422);

        return Inertia::render('Batches/Split', [
            'batch' => [
                'id'               => $batch->id,
                'code'             => $batch->code,
                'product_name'     => $batch->getDisplayName(),
                'current_quantity' => $batch->current_quantity ?? $batch->quantity,
                'unit'             => $batch->unit,
                'status'           => $batch->status,
            ],
        ]);
    }

    public function store(Request $request, Batch $batch)
    {
        abort_unless($request->user()->hasAnyPermission(['enterprise.batches.manage']), 403);
        $tenantId = $this->tenantId($request);
        abort_unless($batch->enterprise_id === $tenantId, 403);
        abort_unless($batch->status === 'active', 422);

        $data = $request->validate([
            'children'              => 'required|array|min:2',
            'children.*.quantity'   => 'required|integer|min:1',
            'children.*.note'       => 'nullable|string|max:255',
            'reason'                => 'nullable|string|max:500',
        ]);

        $totalSplit = collect($data['children'])->sum('quantity');
        $available  = $batch->current_quantity ?? $batch->quantity;

        if ($totalSplit > $available) {
            return back()->withErrors([
                'children' => "Tổng số lượng tách ({$totalSplit}) vượt quá số lượng hiện có ({$available})."
            ]);
        }

        DB::transaction(function () use ($batch, $data, $totalSplit, $tenantId) {
            // Tạo TransformationEvent publish IPFS
            $event = TraceEvent::create([
                'batch_id'    => $batch->id,
                'enterprise_id' => $tenantId,
                'cte_code'    => 'split',
                'event_type'    => 'split',
                'kde_data'    => [
                    'action'      => 'split',
                    'total_split' => $totalSplit,
                    'children'    => $data['children'],
                    'reason'      => $data['reason'] ?? null,
                ],
                'who_name'    => auth()->user()->name,
                'note'        => $data['reason'] ?? null,
                'status'      => 'draft',
                'event_time'  => now(),
            ]);

            // Tạo các lô con
            foreach ($data['children'] as $i => $child) {
                $seq     = str_pad($i + 1, 2, '0', STR_PAD_LEFT);
                $newCode = $batch->code . '-S' . $seq;

                $newBatch = Batch::create([
                    'enterprise_id'      => $tenantId,
                    'origin_enterprise_id' => $batch->origin_enterprise_id ?? $tenantId,
                    'parent_batch_id'    => $batch->id,
                    'product_id'         => $batch->product_id,
                    'code'               => $newCode,
                    'product_name'       => $batch->product_name,
                    'batch_type'         => 'split',
                    'quantity'           => $child['quantity'],
                    'current_quantity'   => $child['quantity'],
                    'unit'               => $batch->unit,
                    'production_date'    => $batch->production_date,
                    'expiry_date'        => $batch->expiry_date,
                    'certifications'       => $batch->certifications ?? [],
                    'status'             => 'active',
                ]);

                BatchLineage::create([
                    'transformation_type' => 'split',
                    'input_batch_id'     => $batch->id,
                    'output_batch_id'    => $newBatch->id,
                    'quantity'           => $child['quantity'],
                    'unit'               => $batch->unit,
                    'event_id'           => $event->id,
                ]);
            }

            // Cập nhật lô gốc
           $remaining = ($batch->current_quantity ?? $batch->quantity) - $totalSplit;

            if ($remaining <= 0) {
                $batch->update([
                    'current_quantity' => 0,
                    'status'           => 'split',
                ]);
            } else {
                // Còn hàng → cập nhật số lượng, vẫn active
                $batch->update([
                    'current_quantity' => $remaining,
                    'status'           => 'active',
                ]);
            }
        });

        return redirect()->route('batches.index')
            ->with('success', "Đã tách lô {$batch->code} thành {$totalSplit} lô con.");
    }
}