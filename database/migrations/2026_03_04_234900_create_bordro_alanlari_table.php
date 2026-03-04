<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordro_alanlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->integer('kod'); // Sıra numarası / Kod
            $table->string('aciklama'); // Örn: NORMAL ÇALIŞMA, FAZLA MESAİ %50
            $table->boolean('gun')->default(false); // Gün checkbox
            $table->boolean('saat')->default(false); // Saat checkbox
            $table->boolean('ucret')->default(false); // Ücret checkbox
            $table->string('bordro_tipi')->default('normal_calisma'); // normal_calisma, fazla_mesai, bilgi, diger_hesaplar_arti, diger_hesaplar_eksi, normal_ek_mesai
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bordro_alanlari');
    }
};
