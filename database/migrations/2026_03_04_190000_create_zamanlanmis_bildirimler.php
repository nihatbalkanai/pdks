<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personele doğum tarihi ekle
        Schema::table('personeller', function (Blueprint $table) {
            $table->date('dogum_tarihi')->nullable()->after('gec_kalma_bildirimi');
        });

        // Zamanlanmış bildirimler tablosu
        Schema::create('zamanlanmis_bildirimler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('ad'); // Görev adı: "Maaş Bildirimi", "Doğum Günü Tebriği" vb.
            $table->enum('tip', ['maas_gunu', 'dogum_gunu', 'bayram', 'ozel_tarih', 'genel']);
            $table->enum('kanal', ['sms', 'email', 'her_ikisi']); // Gönderim kanalı
            $table->string('konu')->nullable(); // Mail konusu
            $table->text('mesaj_sablonu'); // {ad} {soyad} gibi değişkenler içerir
            $table->string('cron_ifadesi')->nullable(); // Özel cron: "0 9 1 * *" gibi
            $table->tinyInteger('gun')->nullable(); // Ayın günü (maaş için: 1-31)
            $table->time('saat')->default('09:00'); // Gönderim saati
            $table->date('ozel_tarih')->nullable(); // Bayram/özel gün tarihi
            $table->boolean('aktif')->default(true);
            $table->timestamp('son_calisma')->nullable();
            $table->integer('toplam_gonderim')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['firma_id', 'tip', 'aktif']);
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn('dogum_tarihi');
        });
        Schema::dropIfExists('zamanlanmis_bildirimler');
    }
};
