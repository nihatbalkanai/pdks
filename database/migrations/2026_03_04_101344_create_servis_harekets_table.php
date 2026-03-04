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
        Schema::create('servis_hareketleri', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('servis_id')->constrained('servisler')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->dateTime('binis_zamani');
            $table->enum('hareket_tipi', ['sabah_binis', 'aksam_binis']);
            $table->timestamps();
            
            $table->index(['firma_id', 'servis_id']);
            $table->index(['firma_id', 'personel_id', 'binis_zamani']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servis_hareketleri');
    }
};
