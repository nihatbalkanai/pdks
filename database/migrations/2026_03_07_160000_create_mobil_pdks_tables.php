<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Firmalar tablosuna mobil/lokasyon alanları ekle
        Schema::table('firmalar', function (Blueprint $table) {
            $table->string('firma_kodu', 20)->nullable()->unique()->after('firma_adi');
            $table->decimal('lokasyon_enlem', 10, 7)->nullable()->after('adres');
            $table->decimal('lokasyon_boylam', 10, 7)->nullable()->after('lokasyon_enlem');
            $table->unsignedSmallInteger('geofence_yaricap')->default(100)->after('lokasyon_boylam'); // metre
            $table->string('wifi_ssid', 255)->nullable()->after('geofence_yaricap');
            $table->boolean('mobil_giris_aktif')->default(false)->after('wifi_ssid');
            $table->boolean('qr_kod_aktif')->default(false)->after('mobil_giris_aktif');
            $table->boolean('gps_zorunlu')->default(true)->after('qr_kod_aktif');
            $table->boolean('selfie_zorunlu')->default(false)->after('gps_zorunlu');
        });

        // Mobil Cihaz Kayıtları
        Schema::create('mobil_cihazlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->string('cihaz_id', 255); // Benzersiz device fingerprint
            $table->string('cihaz_adi', 255)->nullable(); // "iPhone 15 Pro", "Samsung S24" vb
            $table->string('platform', 20)->default('android'); // ios, android, web
            $table->string('os_versiyon', 30)->nullable();
            $table->string('uygulama_versiyon', 20)->nullable();
            $table->string('push_token', 500)->nullable(); // FCM / APNs token
            $table->timestamp('son_giris')->nullable();
            $table->string('son_ip', 45)->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('admin_onayli')->default(false);
            $table->timestamps();
            $table->index(['firma_id', 'personel_id']);
            $table->unique(['firma_id', 'cihaz_id']);
        });

        // Mobil Giriş/Çıkış Hareketleri
        Schema::create('mobil_hareketler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->unsignedBigInteger('mobil_cihaz_id')->nullable();
            $table->enum('tip', ['giris', 'cikis']);
            $table->decimal('enlem', 10, 7)->nullable();
            $table->decimal('boylam', 10, 7)->nullable();
            $table->enum('dogrulama_yontemi', ['gps', 'qr', 'wifi', 'beacon', 'manual'])->default('gps');
            $table->unsignedSmallInteger('mesafe_metre')->nullable(); // Firmaya uzaklık
            $table->string('wifi_ssid', 255)->nullable();
            $table->string('ip_adresi', 45)->nullable();
            $table->boolean('sahte_konum_algilandi')->default(false);
            $table->text('notlar')->nullable();
            $table->timestamps();
            $table->index(['firma_id', 'personel_id', 'created_at']);
        });

        // QR Kod Oturumları (dinamik, süreli)
        Schema::create('qr_kod_oturumlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->string('kod', 64)->unique(); // Benzersiz QR içerik
            $table->string('konum_adi', 255)->nullable(); // "Ana Giriş", "Arka Kapı" vb
            $table->timestamp('gecerlilik_bitis');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->index('firma_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_kod_oturumlari');
        Schema::dropIfExists('mobil_hareketler');
        Schema::dropIfExists('mobil_cihazlar');

        Schema::table('firmalar', function (Blueprint $table) {
            $table->dropColumn([
                'firma_kodu', 'lokasyon_enlem', 'lokasyon_boylam', 'geofence_yaricap',
                'wifi_ssid', 'mobil_giris_aktif', 'qr_kod_aktif', 'gps_zorunlu', 'selfie_zorunlu'
            ]);
        });
    }
};
