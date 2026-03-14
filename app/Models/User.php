<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'enterprise_id',
        'role',
        'permissions',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_super_admin'    => 'boolean',
            'permissions'       => 'array', // tự decode JSON
        ];
    }

    // ── helpers ──────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function isEnterpriseAdmin(): bool
    {
        return $this->role === 'enterprise_admin';
    }

    public function isEnterpriseStaff(): bool
    {
        return $this->role === 'enterprise_staff';
    }

    /**
     * Kiểm tra user có ít nhất 1 trong các permission cho trước.
     * Admin DN luôn trả về true (full access).
     */
    public function hasAnyPermission(array $perms): bool
    {
        if ($this->isEnterpriseAdmin()) return true;

        $userPerms = $this->permissions ?? [];
        foreach ($perms as $p) {
            if (in_array($p, $userPerms, true)) return true;
        }
        return false;
    }

    public function hasAllPermissions(array $perms): bool
    {
        if ($this->isEnterpriseAdmin()) return true;

        $userPerms = $this->permissions ?? [];
        foreach ($perms as $p) {
            if (!in_array($p, $userPerms, true)) return false;
        }
        return true;
    }

    // ── relations ────────────────────────────────────────────

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class, 'enterprise_id');
    }
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}