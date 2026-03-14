<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();   // rau_qua, lua_gao, thuy_san...
            $table->string('name_vi');              // Rau quả tươi
            $table->string('icon', 10)->nullable(); // emoji icon
            $table->string('tcvn_ref')->nullable(); // TCVN 12827:2023
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};