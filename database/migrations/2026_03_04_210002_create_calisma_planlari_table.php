<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calisma_planlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('calisma_grubu_id')->constrained('calisma_gruplari')->onDelete('cascade');
            $table->date('tarih');
            $table->foreignId('vardiya_id')->nullable()->constrained('vardiyalar')->onDelete('set null');
            $table->enum('tur', ['is_gunu', 'tatil', 'resmi_tatil'])->default('is_gunu');
            $table->timestamps();

            $table->index(['firma_id', 'calisma_grubu_id', 'tarih']);
            $table->unique(['calisma_grubu_id', 'tarih']); // Her gün için tek kayıt
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calisma_planlari');
    }
};
