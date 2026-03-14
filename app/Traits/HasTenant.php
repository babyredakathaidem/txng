<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HasTenant
{
    /**
     * Trả về enterprise_id của user đang đăng nhập.
     */
    protected function tenantId(Request $request): int
    {
        return (int) ($request->user()->enterprise_id ?? 0);
    }

    /**
     * Kiểm tra xem object có thuộc về doanh nghiệp hiện tại không.
     */
    protected function assertTenant(Request $request, $model): void
    {
        abort_unless(
            (int) $model->enterprise_id === $this->tenantId($request),
            403,
            'Bạn không có quyền truy cập dữ liệu của doanh nghiệp khác.'
        );
    }
}
