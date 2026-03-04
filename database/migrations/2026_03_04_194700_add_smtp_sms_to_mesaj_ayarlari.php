<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesaj_ayarlari', function (Blueprint $table) {
            // SMTP ayarları
            $table->string('smtp_host')->nullable()->after('durum');
            $table->integer('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_sifreleme')->nullable()->after('smtp_port'); // ssl, tls
            $table->string('smtp_kullanici')->nullable()->after('smtp_sifreleme');
            $table->string('smtp_sifre')->nullable()->after('smtp_kullanici');
            $table->string('gonderen_email')->nullable()->after('smtp_sifre');
            $table->string('gonderen_ad')->nullable()->after('gonderen_email');
            // SMS ayarları
            $table->string('sms_api_url')->nullable()->after('gonderen_ad');
            $table->string('sms_kullanici')->nullable()->after('sms_api_url');
            $table->string('sms_sifre')->nullable()->after('sms_kullanici');
            $table->string('sms_baslik')->nullable()->after('sms_sifre');
        });
    }

    public function down(): void
    {
        Schema::table('mesaj_ayarlari', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host', 'smtp_port', 'smtp_sifreleme', 'smtp_kullanici',
                'smtp_sifre', 'gonderen_email', 'gonderen_ad',
                'sms_api_url', 'sms_kullanici', 'sms_sifre', 'sms_baslik',
            ]);
        });
    }
};
