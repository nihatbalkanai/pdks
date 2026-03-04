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
        Schema::create('aylik_puantaj_parametreleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar');
            
            // Personel tablosundaki veya CalismaGrubu tablosundaki "hesap_parametresi" ile eşleşecek string veya Foreign ID. 
            // Tasarımda "Aylık Puantaj Parametresi" olarak geçiyor
            $table->string('hesap_parametresi_adi', 100); 
            
            // Asıl puantaj ayarları
            $table->integer('aylik_calisma_saati')->default(225); // Örn: 225
            $table->integer('haftalik_calisma_saati')->default(45); // Örn: 45
            $table->decimal('gunluk_calisma_saati', 4, 1)->default(7.5); // Örn: 7.5
            
            $table->boolean('eksik_gun_kesintisi_yapilacak_mi')->default(true);
            
            // Mesai Çarpanları
            $table->decimal('fazla_mesai_carpani', 4, 2)->default(1.50); // Örn: 1.5
            $table->decimal('tatil_mesai_carpani', 4, 2)->default(2.00); // Örn: 2.0
            $table->decimal('resmi_tatil_mesai_carpani', 4, 2)->default(2.00); // Örn: 2.0
            
            $table->boolean('durum')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aylik_puantaj_parametreleri');
    }
};
