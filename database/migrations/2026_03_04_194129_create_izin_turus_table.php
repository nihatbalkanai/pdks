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
        Schema::create('izin_turleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('ad'); // Örn: Yıllık İzin, Ücretsiz İzin, Mazeret İzni
            $table->boolean('ucret_kesintisi_yapilacak_mi')->default(false); // true ise maaştan eksik gün sayılır
            $table->boolean('yillik_izinden_duser_mi')->default(false); // İsteğe bağlı eklendi: Yıllık İzin bakiyesinden düşer mi
            $table->boolean('aktif_mi')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izin_turleri');
    }
};
