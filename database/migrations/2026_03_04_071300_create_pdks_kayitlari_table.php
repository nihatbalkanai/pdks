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
        Schema::create('pdks_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('cihaz_id')->constrained('pdks_cihazlari')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->timestamp('kayit_tarihi')->index();
            $table->string('islem_tipi', 20)->comment('giriş veya çıkış');
            $table->json('ham_veri')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Performans için arama indexleri 
            $table->index('cihaz_id');
            $table->index('personel_id');
            $table->index(['firma_id', 'kayit_tarihi'], 'idx_firma_kayit'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdks_kayitlari');
    }
};
