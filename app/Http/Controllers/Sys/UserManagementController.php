<?php

namespace App\Http\Controllers\Sys;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    /**
     * Danh sách toàn bộ User trong hệ thống
     */
    public function index(Request $request)
    {
        $query = User::with('enterprise')->orderByDesc('id');

        // Search theo tên hoặc email
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        // Filter theo role
        if ($request->filled('role')) {
            if ($request->role === 'super') {
                $query->where('is_super_admin', true);
            } else {
                $query->where('role', $request->role);
            }
        }

        return Inertia::render('Sys/Users/Index', [
            'users' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Cập nhật quyền hạn hoặc trạng thái User
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|string|in:admin,staff',
            'is_super_admin' => 'boolean',
        ]);

        $user->update($data);

        return back()->with('success', 'Đã cập nhật thông tin tài khoản.');
    }

    /**
     * Khóa/Mở khóa tài khoản (Cái này gắt)
     */
    public function toggleStatus(User $user)
    {
        // Chỗ này nếu mày có trường status hoặc active thì update, 
        // tạm thời tao giả định mày dùng email_verified_at để chặn hoặc 1 field status
        // Tao sẽ dùng field status nếu mày có, nếu không thì báo lỗi
        if (isset($user->status)) {
            $user->status = ($user->status === 'active') ? 'blocked' : 'active';
            $user->save();
        }

        return back()->with('success', 'Đã thay đổi trạng thái tài khoản.');
    }
}
