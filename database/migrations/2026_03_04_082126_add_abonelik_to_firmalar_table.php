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
            $table->date('abonelik_bitis_tarihi')->nullable()->after('durum');
            $table->enum('paket_tipi', ['Ücretsiz', 'Standart', 'Pro'])->default('Ücretsiz')->after('abonelik_bitis_tarihi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn(['abonelik_bitis_tarihi', 'paket_tipi']);
        });
    }
};
