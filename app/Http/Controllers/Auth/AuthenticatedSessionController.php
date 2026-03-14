<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Enterprise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // ── Super Admin → sys dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('sys.enterprises.index');
        }

        // ── Chưa có DN → bắt buộc đăng ký
        if (!$user->enterprise_id) {
            return redirect()->route('onboarding.enterprise.create');
        }

        // ── Có DN → kiểm tra trạng thái
        $enterprise = Enterprise::find($user->enterprise_id);

        if (!$enterprise || $enterprise->status === 'pending') {
            return redirect()->route('onboarding.enterprise.pending');
        }

        if ($enterprise->status === 'rejected') {
            return redirect()->route('onboarding.enterprise.rejected');
        }

        // ── approved → vào app
        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}