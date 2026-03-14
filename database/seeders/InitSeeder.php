<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            'name'           => 'Super Admin',
            'email'          => 'super@agu.vn',
            'password'       => Hash::make('12345678'),
            'is_super_admin' => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ── Doanh nghiệp demo ────────────────────────────────
        $enterpriseId = DB::table('enterprises')->insertGetId([
            'name'         => 'DN Demo',
            // Không có tax_code, address — đã drop
            'province'     => 'An Giang',
            'district'     => 'Long Xuyên',
            'address_detail' => '123 Nguyễn Huệ',
            'phone'        => '0900000000',
            'email'        => 'demo@dn.vn',
            'status'       => 'approved',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── Admin DN ─────────────────────────────────────────
        $adminId = DB::table('users')->insertGetId([
            'name'          => 'Admin DN',
            'email'         => 'admin@demo.com',
            'password'      => Hash::make('12345678'),
            'enterprise_id' => $enterpriseId,
            'role'          => 'enterprise_admin',
            'permissions'   => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('enterprises')->where('id', $enterpriseId)->update([
            'admin_user_id' => $adminId,
            'created_by'    => $adminId,
        ]);

        // ── Nhân viên DN ─────────────────────────────────────
        DB::table('users')->insertOrIgnore([
            'name'          => 'Nhân viên Demo',
            'email'         => 'staff@demo.com',
            'password'      => Hash::make('12345678'),
            'enterprise_id' => $enterpriseId,
            'role'          => 'enterprise_staff',
            'permissions'   => json_encode([
                'enterprise.products.view',
                'enterprise.batches.view',
                'enterprise.trace_events.view',
                'enterprise.trace_events.create',
                'enterprise.qrcodes.view',
            ]),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}