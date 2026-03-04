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
        // 1. firmalar tablosu
        Schema::create('firmalar', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('firma_adi');
            $table->string('vergi_no')->index();
            $table->string('vergi_dairesi')->nullable();
            $table->text('adres')->nullable();
            $table->boolean('durum')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. kullanicilar tablosu 
        Schema::create('kullanicilar', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('ad_soyad');
            $table->string('eposta')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('sifre'); 
            $table->string('rol')->default('kullanici');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
            
            // firma_id foreignId olarak indexleniyor ama ayrıca istenen index
            $table->index('firma_id');
        });

        // 3. pdks_cihazlari tablosu
        Schema::create('pdks_cihazlari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('seri_no')->index();
            $table->string('cihaz_modeli')->nullable();
            $table->timestamp('son_aktivite_tarihi')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('firma_id');
        });

        // 4. Parola Sıfırlama Tokenleri
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 5. Oturumlar (Sessions)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdks_cihazlari');
        Schema::dropIfExists('kullanicilar');
        Schema::dropIfExists('firmalar');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
