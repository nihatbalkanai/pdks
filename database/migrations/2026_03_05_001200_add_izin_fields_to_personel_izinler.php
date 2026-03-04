<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personel_izinler', function (Blueprint $table) {
            // İzin türü ile ilişkilendir
            $table->foreignId('izin_turu_id')->nullable()->after('personel_id')->constrained('izin_turleri')->onDelete('set null');
            // Başlangıç-Bitiş tarihi için
            $table->date('bitis_tarihi')->nullable()->after('tarih');
            // Toplam gün sayısı
            $table->decimal('gun_sayisi', 5, 1)->default(1)->after('bitis_tarihi');
            // Onay durumu
            $table->enum('durum', ['beklemede', 'onaylandi', 'reddedildi'])->default('beklemede')->after('aciklama');
            // Onaylayan kişi
            $table->foreignId('onaylayan_id')->nullable()->after('durum')->constrained('kullanicilar')->onDelete('set null');
            // Belge/Rapor dosya yolu
            $table->string('belge_yolu')->nullable()->after('onaylayan_id');
            // Soft delete
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('personel_izinler', function (Blueprint $table) {
            $table->dropForeign(['izin_turu_id']);
            $table->dropForeign(['onaylayan_id']);
            $table->dropColumn(['izin_turu_id', 'bitis_tarihi', 'gun_sayisi', 'durum', 'onaylayan_id', 'belge_yolu', 'deleted_at']);
        });
    }
};
