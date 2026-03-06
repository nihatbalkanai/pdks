<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aylık puantaj parametrelerine fazla mesai toleransı ve gün fark ayarları eklenir.
     * Bu parametreler firma bazlı ayarlanabilir, UI üzerinden değiştirilebilir.
     */
    public function up(): void
    {
        Schema::table('aylik_puantaj_parametreleri', function (Blueprint $table) {
            // Fazla mesai toleransı (dakika cinsinden)
            // Bu sürenin altındaki fazla çalışmalar FM olarak sayılmaz
            // Örn: 5 dk → 07:55'te gelen personelin 5 dk'sı FM yapılmaz
            $table->integer('fazla_mesai_tolerans_dakika')->default(5)->after('resmi_tatil_mesai_carpani')
                ->comment('FM tolerans süresi (dk). Bu sürenin altındaki fazla çalışma FM sayılmaz');

            // Gün fark hesabı yapılsın mı?
            // Tam ay çalışan personelde takvim günü < 30 ise fark ücreti eklenir
            $table->boolean('gun_fark_hesapla')->default(true)->after('fazla_mesai_tolerans_dakika')
                ->comment('Tam ay çalışanda takvim günü farkı (30 - ayGünü) ücreti eklensin mi');

            // SSK rapor ödemesi toplama dahil mi?
            $table->boolean('ssk_rapor_toplama_dahil')->default(false)->after('gun_fark_hesapla')
                ->comment('SSK rapor ödemesi bankaya yatan toplama dahil edilsin mi');
        });
    }

    public function down(): void
    {
        Schema::table('aylik_puantaj_parametreleri', function (Blueprint $table) {
            $table->dropColumn(['fazla_mesai_tolerans_dakika', 'gun_fark_hesapla', 'ssk_rapor_toplama_dahil']);
        });
    }
};
