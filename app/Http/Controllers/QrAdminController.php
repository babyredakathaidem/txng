<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Qrcode;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QrAdminController extends Controller
{
    public function index(Batch $batch)
    {
        abort_unless(auth()->user()->hasAnyPermission(['enterprise.qrcodes.view', 'enterprise.qrcodes.manage']), 403);
        $qrs = Qrcode::where('batch_id', $batch->id)->orderBy('type')->get();

        return Inertia::render('Batches/Qrs', [
            'batch' => $batch->only(['id','code','product_name','enterprise_id']),
            'qrs' => $qrs,
            'publicUrlBase' => url('/t'),
            'privateUrlBase' => url('/v'),
        ]);
    }

    public function ensure(Batch $batch, QrCodeService $svc)
    {
        abort_unless(auth()->user()->hasAnyPermission(['enterprise.qrcodes.manage']), 403);
        $svc->ensureForBatch($batch->enterprise_id, $batch->id);
        return redirect()->route('batches.qrs', $batch->id);
    }

    public function configurePublic(Request $request, Qrcode $qrcode)
    {
        abort_unless(auth()->user()->hasAnyPermission(['enterprise.qrcodes.manage']), 403);
        abort_unless($qrcode->type === 'public', 403);

        $data = $request->validate([
            'place_name' => ['required','string','max:255'],
            'allowed_lat' => ['required','numeric','between:-90,90'],
            'allowed_lng' => ['required','numeric','between:-180,180'],
            'allowed_radius_m' => ['required','integer','min:1','max:5000'],
        ]);

        $qrcode->update($data);

        return back()->with('success', 'Đã cấu hình QR public.');
    }
}
