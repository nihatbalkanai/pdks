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
        Schema::table('personel_izinler', function (Blueprint $table) {
            $table->decimal('ssk_odeme_tutari', 15, 2)->nullable()->after('gun_sayisi')->comment('SSK tarafından yapılan rapor ödeme tutarı (sadece Raporlarda)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personel_izinler', function (Blueprint $table) {
            $table->dropColumn('ssk_odeme_tutari');
        });
    }
};
