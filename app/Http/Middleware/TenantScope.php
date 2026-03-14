<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TenantScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Super admin xem mọi tenant -> không scope
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Nếu chưa có enterprise_id thì EnsureEnterprise sẽ redirect rồi,
        // nhưng để an toàn, chặn luôn.
        if (!$user->enterprise_id) {
            return redirect()->route('onboarding.enterprise.create');
        }

        // Global scope cho tất cả query Eloquent có enterprise_id
        // (cẩn thận: nếu model không có enterprise_id thì ignore)
        $enterpriseId = (int) $user->enterprise_id;

        Builder::macro('forTenant', function () use ($enterpriseId) {
            /** @var Builder $this */
            return $this->where($this->getModel()->getTable() . '.enterprise_id', $enterpriseId);
        });

        // Nếu ba đang dùng global scope ở model rồi thì đoạn macro này không bắt buộc,
        // nhưng để thống nhất thì giữ.

        app()->instance('tenant.enterprise_id', $enterpriseId);

        return $next($request);
    }
}
