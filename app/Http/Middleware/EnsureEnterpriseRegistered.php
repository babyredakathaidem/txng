<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEnterpriseRegistered
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // ✅ Super admin bỏ qua hoàn toàn
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        // ✅ (option) Nếu đang ở sys route thì cũng không ép onboarding
        if ($request->routeIs('sys.*')) {
            return $next($request);
        }

        // Chưa có DN -> ép về onboarding
        if ($user && !$user->enterprise_id) {
            if (!$request->routeIs('onboarding.enterprise.*')) {
                return redirect()->route('onboarding.enterprise.create');
            }
        }

        return $next($request);
    }
}