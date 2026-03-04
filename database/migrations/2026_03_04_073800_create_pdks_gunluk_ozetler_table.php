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
        Schema::create('pdks_gunluk_ozetler', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->date('tarih');
            $table->dateTime('ilk_giris')->nullable();
            $table->dateTime('son_cikis')->nullable();
            $table->integer('toplam_calisma_suresi')->default(0)->comment('Dakika cinsinden toplam mesai');
            $table->string('durum', 30)->default('gelmedi')->comment('geldi, geç kaldı, gelmedi');
            $table->softDeletes();
            $table->timestamps();

            // Tüm indeksler performans gereği firma_id ile başlar. Seçmeli aramalar için composite yapıldı.
            $table->index(['firma_id', 'tarih'], 'idx_firma_tarih');
            $table->index(['firma_id', 'personel_id', 'tarih'], 'idx_firma_personel_tarih');
            $table->index(['firma_id', 'durum'], 'idx_firma_durum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdks_gunluk_ozetler');
    }
};
