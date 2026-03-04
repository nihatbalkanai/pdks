<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calisma_gruplari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('aciklama', 100);          // MAVİ YAKA
            $table->string('hesap_parametresi', 100)->nullable(); // GENEL AYLIK PAR.
            $table->boolean('durum')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calisma_gruplari');
    }
};
