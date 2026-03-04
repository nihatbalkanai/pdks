<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gunluk_puantaj_parametreleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('ad'); // Örn: HAFTA İÇİ MAVİ YAKA
            
            // Genel Sekmesi
            $table->time('gun_donum_saati')->default('06:00');
            $table->time('iceri_giris_saati')->default('08:30');
            $table->time('disari_cikis_saati')->default('18:00');
            $table->time('erken_gelme_toleransi')->default('08:00');
            $table->time('gec_gelme_toleransi')->default('22:22');
            $table->time('erken_cikma_toleransi')->default('22:22');
            $table->string('hesaplama_tipi')->default('normal_toplam'); // normal_toplam, net_toplam
            
            // Mola / Ceza Sekmesi
            $table->integer('mola_suresi')->default(0); // dk cinsinden
            $table->integer('gec_gelme_cezasi')->default(0); // dk cinsinden
            $table->integer('erken_cikma_cezasi')->default(0); // dk cinsinden
            
            $table->boolean('durum')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gunluk_puantaj_bordro_alanlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gunluk_puantaj_id')->constrained('gunluk_puantaj_parametreleri')->onDelete('cascade');
            $table->string('bordro_alani'); // Örn: FAZLA MESAİ %50
            $table->time('basla')->nullable(); // Başlangıç saati
            $table->time('bitis')->nullable(); // Bitiş saati
            $table->time('min_sure')->nullable(); // Min süre
            $table->time('max_sure')->nullable(); // Max süre
            $table->time('ekle')->nullable(); // Ek süre
            $table->integer('carpan')->default(100); // Örn: 150 = %150
            $table->string('ucret')->default('ucret_1'); // Ücret alanı referansı
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gunluk_puantaj_bordro_alanlari');
        Schema::dropIfExists('gunluk_puantaj_parametreleri');
    }
};
