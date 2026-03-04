<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vardiyalar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('ad', 100);                    // HAFTA İÇİ MAVİ YAKA
            $table->time('baslangic_saati')->nullable();  // 08:00
            $table->time('bitis_saati')->nullable();      // 17:00
            $table->integer('toplam_sure')->nullable();   // dakika
            $table->string('renk', 20)->default('#3B82F6'); // takvim rengi
            $table->boolean('durum')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('firma_id');
            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vardiyalar');
    }
};
