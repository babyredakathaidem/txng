<?php

namespace App\Http\Middleware;

use App\Models\Enterprise;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => function () use ($request) {
                    $u = $request->user();
                    if (!$u) return null;

                    $permissions = [];
                    if ($u->isSuperAdmin()) {
                        $permissions = ['sys.enterprises.view', 'sys.enterprises.approve', 'sys.settings.manage'];
                    } elseif ($u->isEnterpriseAdmin()) {
                        $permissions = [
                            'enterprise.products.view',
                            'enterprise.products.manage',
                            'enterprise.batches.view',
                            'enterprise.batches.manage',
                            'enterprise.trace_events.view',
                            'enterprise.trace_events.create',
                            'enterprise.trace_events.manage',
                            'enterprise.qrcodes.view',
                            'enterprise.qrcodes.manage',
                            'enterprise.users.manage',
                            'enterprise.settings.manage',
                        ];
                    } else {
                        $permissions = $u->permissions ?? [];
                    }

                    return [
                        'id'            => $u->id,
                        'name'          => $u->name,
                        'email'         => $u->email,
                        'enterprise_id' => $u->enterprise_id,
                        'is_super_admin'=> $u->isSuperAdmin(),
                        'role'          => $u->role,
                        'permissions'   => array_values($permissions),
                    ];
                },
                // ── Tên + code DN cho topbar ───────────────────────
                'enterprise' => function () use ($request) {
                    $u = $request->user();
                    if (!$u || !$u->enterprise_id) return null;
                    $e = Enterprise::find($u->enterprise_id, ['id', 'name', 'code', 'status']);
                    if (!$e) return null;
                    return [
                        'id'     => $e->id,
                        'name'   => $e->name,
                        'code'   => $e->code,
                        'status' => $e->status,
                    ];
                },
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}