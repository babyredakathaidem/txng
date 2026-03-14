<?php

namespace App\Http\Middleware;

use App\Models\Enterprise;
use Closure;
use Illuminate\Http\Request;

class EnsureEnterprise
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Chưa login thì thôi (route auth sẽ tự handle)
        if (!$user) {
            return $next($request);
        }

        // Super admin được bỏ qua toàn bộ yêu cầu DN
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Cho phép user truy cập các route onboarding để khỏi loop
        if ($request->routeIs('onboarding.enterprise.*')) {
            return $next($request);
        }

        // Chưa có DN -> ép đi đăng ký DN
        if (!$user->enterprise_id) {
            return redirect()->route('onboarding.enterprise.create');
        }

        $enterprise = Enterprise::query()->find($user->enterprise_id);

        // DN không tồn tại (xóa nhầm) -> reset và ép onboarding lại
        if (!$enterprise) {
            $user->enterprise_id = null;
            $user->save();

            return redirect()->route('onboarding.enterprise.create');
        }

        // Điều hướng theo trạng thái
        $status = $enterprise->status ?? 'pending';

        if ($status === 'pending') {
            return redirect()->route('onboarding.enterprise.pending');
        }

        if ($status === 'rejected') {
            return redirect()->route('onboarding.enterprise.rejected');
        }
        if ($status === 'blocked') {
            return redirect()->route('onboarding.enterprise.blocked');
        }
        // approved -> cho qua
        return $next($request);
    }
}
