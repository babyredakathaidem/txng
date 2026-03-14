<?php

namespace App\Http\Controllers\Sys;

use App\Http\Controllers\Controller;
use App\Mail\EnterpriseApprovedMail;
use App\Mail\EnterpriseRejectedMail;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Mail\EnterpriseBlockedMail;

class EnterpriseApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $q = Enterprise::query()->orderByDesc('id');
        if ($status !== 'all') $q->where('status', $status);

        return Inertia::render('Sys/Enterprises', [
            'status'      => $status,
            'enterprises' => $q->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Enterprise $enterprise)
    {
        $fileUrl = $enterprise->business_cert_file_path
            ? Storage::disk('public')->url($enterprise->business_cert_file_path)
            : null;

        return Inertia::render('Sys/EnterpriseShow', [
            'enterprise' => $enterprise,
            'fileUrl'    => $fileUrl,
        ]);
    }

    public function approve(Request $request, Enterprise $enterprise)
    {
        DB::transaction(function () use ($request, $enterprise) {
            $enterprise->status          = 'approved';
            $enterprise->approved_at     = now();
            $enterprise->approved_by     = $request->user()->id;
            $enterprise->rejected_at     = null;
            $enterprise->rejected_by     = null;
            $enterprise->rejection_reason = null;
            $enterprise->save();
        });

        // ── Gửi mail thông báo cho admin DN
        if ($enterprise->email) {
            Mail::to($enterprise->email)->queue(new EnterpriseApprovedMail($enterprise));
        }

        return back()->with('success', 'Đã duyệt doanh nghiệp.');
    }

    public function reject(Request $request, Enterprise $enterprise)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $enterprise, $data) {
            $enterprise->status           = 'rejected';
            $enterprise->rejected_at      = now();
            $enterprise->rejected_by      = $request->user()->id;
            $enterprise->rejection_reason = $data['reason'];
            $enterprise->approved_at      = null;
            $enterprise->approved_by      = null;
            $enterprise->save();
        });

        // ── Gửi mail thông báo cho admin DN
        if ($enterprise->email) {
            Mail::to($enterprise->email)->queue(new EnterpriseRejectedMail($enterprise));
        }

        return back()->with('success', 'Đã từ chối doanh nghiệp.');
    }
    public function block(Request $request, Enterprise $enterprise)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $enterprise->update([
            'status'        => 'blocked',
            'blocked_at'    => now(),
            'blocked_by'    => $request->user()->id,
            'blocked_reason'=> $data['reason'],
        ]);

        if ($enterprise->email) {
            Mail::to($enterprise->email)->queue(new EnterpriseBlockedMail($enterprise));
        }

        return back()->with('success', 'Đã khóa doanh nghiệp.');
    }

    public function unblock(Request $request, Enterprise $enterprise)
    {
        $enterprise->update([
            'status'         => 'approved',
            'blocked_at'     => null,
            'blocked_by'     => null,
            'blocked_reason' => null,
        ]);

        return back()->with('success', 'Đã mở khóa doanh nghiệp.');
    }
}