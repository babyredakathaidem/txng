<?php

namespace App\Http\Controllers;

use App\Models\Enterprise;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingEnterpriseStatusController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->enterprise_id) {
            return response()->json(['has_enterprise' => false, 'status' => null]);
        }

        $enterprise = Enterprise::query()->find($user->enterprise_id);

        if (!$enterprise) {
            return response()->json(['has_enterprise' => false, 'status' => null]);
        }

        return response()->json([
            'has_enterprise' => true,
            'status' => $enterprise->status ?? 'pending',
        ]);
    }

    public function pending(Request $request)
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        if (!$user->enterprise_id) {
            return redirect()->route('onboarding.enterprise.create');
        }

        $enterprise = Enterprise::query()->find($user->enterprise_id);

        if (!$enterprise) {
            $user->enterprise_id = null;
            $user->save();
            return redirect()->route('onboarding.enterprise.create');
        }

        if (($enterprise->status ?? 'pending') === 'approved') {
            return redirect()->route('dashboard');
        }

        if (($enterprise->status ?? 'pending') === 'rejected') {
            return redirect()->route('onboarding.enterprise.rejected');
        }

        return Inertia::render('Onboarding/EnterprisePending', [
            'enterprise' => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'code' => $enterprise->code,
                'status' => $enterprise->status,
            ],
        ]);
    }

    public function rejected(Request $request)
    {
        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        if (!$user->enterprise_id) {
            return redirect()->route('onboarding.enterprise.create');
        }

        $enterprise = Enterprise::query()->find($user->enterprise_id);

        if (!$enterprise) {
            $user->enterprise_id = null;
            $user->save();
            return redirect()->route('onboarding.enterprise.create');
        }

        if (($enterprise->status ?? 'pending') === 'approved') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/EnterpriseRejected', [
            'rejection_reason' => $enterprise->rejection_reason,
            'enterprise' => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'code' => $enterprise->code,
                'status' => $enterprise->status,
            ],
        ]);
    }
    public function blocked(Request $request)
    {
        $enterprise = Enterprise::find($request->user()->enterprise_id);

        if (!$enterprise || $enterprise->status !== 'blocked') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/EnterpriseBlocked', [
            'blocked_reason' => $enterprise->blocked_reason,
            'enterprise'     => ['name' => $enterprise->name],
        ]);
    }
}