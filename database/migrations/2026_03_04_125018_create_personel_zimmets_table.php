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
        Schema::create('personel_zimmetler', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->string('kategori')->nullable();
            $table->string('bolum_adi')->nullable();
            $table->string('aciklama')->nullable();
            $table->date('verilis_tarihi')->nullable();
            $table->date('iade_tarihi')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'personel_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personel_zimmetler');
    }
};
