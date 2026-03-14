<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

trait NotifiesEnterpriseAdmins
{
    /**
     * Gửi mail cho tất cả admin của một doanh nghiệp.
     */
    protected function notifyEnterpriseAdmins(int $enterpriseId, $mailable): void
    {
        try {
            $admins = User::where('enterprise_id', $enterpriseId)
                ->where('role', 'enterprise_admin')
                ->get(['email']);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue($mailable);
            }
        } catch (\Throwable $e) {
            Log::warning("notifyEnterpriseAdmins failed: " . $e->getMessage());
        }
    }
}
