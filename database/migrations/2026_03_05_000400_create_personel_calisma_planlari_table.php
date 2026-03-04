<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personel_calisma_planlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->date('tarih');
            $table->foreignId('vardiya_id')->nullable()->constrained('vardiyalar')->onDelete('set null');
            $table->string('tur')->default('is_gunu'); // is_gunu, tatil, resmi_tatil, izin
            $table->string('aciklama')->nullable(); // Ek not (Örn: Nöbet, Telafi vb.)
            $table->timestamps();

            $table->index(['firma_id', 'personel_id', 'tarih']);
            $table->unique(['personel_id', 'tarih']); // Her personel - her gün tek kayıt
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_calisma_planlari');
    }
};
