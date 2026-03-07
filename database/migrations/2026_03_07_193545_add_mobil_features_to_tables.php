<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->string('logo_yolu')->nullable()->after('firma_adi');
        });

        Schema::table('mobil_cihazlar', function (Blueprint $table) {
            $table->string('push_token')->nullable()->after('son_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn('logo_yolu');
        });

        Schema::table('mobil_cihazlar', function (Blueprint $table) {
            $table->dropColumn('push_token');
        });
    }
};
