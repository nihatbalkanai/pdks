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
        Schema::table('personeller', function (Blueprint $table) {
            $table->string('kart_no')->nullable()->after('uuid');
            $table->string('ad')->nullable()->after('firma_id');
            $table->string('soyad')->nullable()->after('ad');
            $table->string('ssk_no')->nullable()->after('sicil_no');
            $table->string('unvan')->nullable()->after('ssk_no');
            $table->string('sirket')->nullable()->after('unvan');
            // bolum already exists
            $table->string('ozel_kod')->nullable()->after('bolum');
            $table->string('departman')->nullable()->after('ozel_kod');
            $table->string('servis_kodu')->nullable()->after('departman');
            $table->string('hesap_gurubu')->nullable()->after('servis_kodu');
            $table->string('agi')->nullable()->after('hesap_gurubu');
            $table->decimal('aylik_ucret', 15, 2)->nullable()->after('agi');
            $table->decimal('gunluk_ucret', 15, 2)->nullable()->after('aylik_ucret');
            $table->decimal('saat_1', 15, 5)->nullable()->after('gunluk_ucret');
            $table->decimal('saat_2', 15, 5)->nullable()->after('saat_1');
            $table->decimal('saat_3', 15, 5)->nullable()->after('saat_2');
            $table->date('giris_tarihi')->nullable()->after('saat_3');
            $table->date('cikis_tarihi')->nullable()->after('giris_tarihi');
            $table->string('resim_yolu')->nullable()->after('cikis_tarihi');
        });

        // We could drop ad_soyad but let's keep it to prevent immediate UI breakage until we update Vue
        // Alternatively, we can let Vue use 'ad' and 'soyad' exclusively. Actually, the requirement asks for "Ad" and "Soyad" separate.
        // I will add them and later we migrate the data using Tinker if necessary.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn([
                'kart_no', 'ad', 'soyad', 'ssk_no', 'unvan', 'sirket', 'ozel_kod',
                'departman', 'servis_kodu', 'hesap_gurubu', 'agi', 'aylik_ucret',
                'gunluk_ucret', 'saat_1', 'saat_2', 'saat_3', 'giris_tarihi', 'cikis_tarihi', 'resim_yolu'
            ]);
        });
    }
};
