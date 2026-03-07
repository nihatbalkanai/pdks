<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Destek Biletleri
        Schema::create('destek_biletleri', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('olusturan_id'); // Kullanıcı ID
            $table->string('konu');
            $table->enum('kategori', ['teknik', 'fatura', 'genel', 'ozellik_talebi'])->default('genel');
            $table->enum('oncelik', ['dusuk', 'normal', 'yuksek', 'acil'])->default('normal');
            $table->enum('durum', ['acik', 'yanit_bekleniyor', 'cevaplandi', 'cozuldu', 'kapatildi'])->default('acik');
            $table->timestamp('son_yanit_tarihi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('firma_id');
            $table->index('durum');
        });

        // Bilet Mesajları
        Schema::create('bilet_mesajlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bilet_id');
            $table->unsignedBigInteger('gonderen_id');
            $table->enum('gonderen_tipi', ['musteri', 'admin'])->default('musteri');
            $table->text('mesaj');
            $table->string('dosya_yolu')->nullable();
            $table->timestamps();

            $table->foreign('bilet_id')->references('id')->on('destek_biletleri')->onDelete('cascade');
            $table->index('bilet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilet_mesajlari');
        Schema::dropIfExists('destek_biletleri');
    }
};
