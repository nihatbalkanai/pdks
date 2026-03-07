<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paket tanımları tablosu
        Schema::create('paket_tanimlari', function (Blueprint $table) {
            $table->id();
            $table->string('ad')->unique(); // Ücretsiz, Standart, Pro, Enterprise
            $table->string('kod')->unique(); // ucretsiz, standart, pro, enterprise
            $table->integer('max_personel')->default(0); // 0 = sınırsız
            $table->integer('max_kullanici')->default(0);
            $table->integer('max_cihaz')->default(0);
            $table->decimal('aylik_fiyat', 10, 2)->default(0);
            $table->decimal('yillik_fiyat', 10, 2)->default(0);
            $table->json('ozellikler')->nullable(); // Ek özellikler listesi
            $table->text('aciklama')->nullable();
            $table->string('renk', 7)->default('#6366f1'); // Paket badge rengi
            $table->integer('sira')->default(0); // Görüntüleme sırası
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Aktivite logları
        Schema::create('platform_aktivite_loglari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kullanici_id')->nullable();
            $table->string('islem'); // firma_olusturuldu, abonelik_guncellendi, vb.
            $table->string('hedef_tip')->nullable(); // firma, kullanici, duyuru
            $table->string('hedef_id')->nullable();
            $table->text('detay')->nullable();
            $table->string('ip_adresi', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_aktivite_loglari');
        Schema::dropIfExists('paket_tanimlari');
    }
};
