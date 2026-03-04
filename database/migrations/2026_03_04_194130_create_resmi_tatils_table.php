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
        Schema::create('resmi_tatiller', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->integer('yil'); // Örn: 2026
            $table->date('tarih');
            $table->string('ad'); // Örn: 23 Nisan Ulusal Egemenlik ve Çocuk Bayramı
            $table->string('tur')->nullable(); // Dini Bayram / Resmi Tatil vb.
            $table->boolean('yarim_gun_mu')->default(false); // Arifeler için
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['firma_id', 'tarih']); // Bir firmada aynı güne iki resmi tatil girilmemesi için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resmi_tatiller');
    }
};
