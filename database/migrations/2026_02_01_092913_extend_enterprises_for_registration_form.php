<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            // system tenant code: DN-000001 (đã có code rồi thì bỏ qua)
            if (!Schema::hasColumn('enterprises', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('enterprises', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }

            // ====== Thông tin theo form đăng ký DN ======
            if (!Schema::hasColumn('enterprises', 'business_code')) {
                $table->string('business_code', 100)->nullable()->after('name'); // "Mã số doanh nghiệp"
            }
            if (!Schema::hasColumn('enterprises', 'business_code_issued_at')) {
                $table->date('business_code_issued_at')->nullable()->after('business_code');
            }

            if (!Schema::hasColumn('enterprises', 'business_cert_no')) {
                $table->string('business_cert_no', 100)->nullable()->after('business_code_issued_at'); // "Số GCN ĐKDN"
            }
            if (!Schema::hasColumn('enterprises', 'business_cert_issued_place')) {
                $table->string('business_cert_issued_place', 255)->nullable()->after('business_cert_no'); // "Nơi cấp GCN"
            }

            if (!Schema::hasColumn('enterprises', 'business_license_no')) {
                $table->string('business_license_no', 100)->nullable()->after('business_cert_issued_place'); // "Số giấy phép KD"
            }
            if (!Schema::hasColumn('enterprises', 'business_license_issued_place')) {
                $table->string('business_license_issued_place', 255)->nullable()->after('business_license_no'); // "Nơi cấp giấy phép"
            }

            if (!Schema::hasColumn('enterprises', 'province')) {
                $table->string('province', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('enterprises', 'district')) {
                $table->string('district', 100)->nullable()->after('province');
            }
            if (!Schema::hasColumn('enterprises', 'address_detail')) {
                $table->string('address_detail', 255)->nullable()->after('district'); // "Địa chỉ cụ thể"
            }

            if (!Schema::hasColumn('enterprises', 'representative_name')) {
                $table->string('representative_name', 255)->nullable()->after('email'); // "Họ tên người đại diện"
            }
            if (!Schema::hasColumn('enterprises', 'representative_id')) {
                $table->string('representative_id', 100)->nullable()->after('representative_name'); // "CCCD/Hộ chiếu"
            }

            if (!Schema::hasColumn('enterprises', 'business_cert_file_path')) {
                $table->string('business_cert_file_path', 500)->nullable()->after('representative_id'); // file upload
            }

            if (!Schema::hasColumn('enterprises', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('business_cert_file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprises', function (Blueprint $table) {
            $drop = [
                'business_code',
                'business_code_issued_at',
                'business_cert_no',
                'business_cert_issued_place',
                'business_license_no',
                'business_license_issued_place',
                'province',
                'district',
                'address_detail',
                'representative_name',
                'representative_id',
                'business_cert_file_path',
                'terms_accepted_at',
            ];

            foreach ($drop as $col) {
                if (Schema::hasColumn('enterprises', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
