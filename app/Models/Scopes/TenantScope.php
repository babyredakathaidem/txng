<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Chưa login thì khỏi show gì (tránh leak)
        if (!$user) {
            $builder->whereRaw('1=0');
            return;
        }

        // ✅ Super admin: bypass tenant filter
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        // ✅ User thường: phải có enterprise_id
        if (!empty($user->enterprise_id)) {
            $builder->where($model->getTable() . '.enterprise_id', $user->enterprise_id);
            return;
        }

        // Chưa có tenant -> không cho thấy dữ liệu
        $builder->whereRaw('1=0');
    }
}
