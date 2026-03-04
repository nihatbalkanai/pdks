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
        Schema::create('sistem_loglari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('kullanici_id')->nullable()->constrained('kullanicilar')->nullOnDelete();
            $table->string('islem');
            $table->text('detay')->nullable();
            $table->string('ip_adresi')->nullable();
            $table->timestamp('tarih')->useCurrent();
            $table->timestamps();
            
            // Performans indeksleri
            $table->index(['firma_id', 'tarih']);
            $table->index('islem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sistem_loglari');
    }
};
