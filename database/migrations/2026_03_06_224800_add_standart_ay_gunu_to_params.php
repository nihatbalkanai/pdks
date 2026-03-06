<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SGK veya yurtdışı standartlarına göre maaşın bölüneceği gün sayısını dinamik hale getirir.
     */
    public function up(): void
    {
        Schema::table('aylik_puantaj_parametreleri', function (Blueprint $table) {
            $table->integer('standart_ay_gunu')->default(30)->after('gunluk_calisma_saati')
                ->comment('Maaşın bölüneceği takvim/standart gün sayısı (Türkiye SGK için her zaman 30)');
        });
    }

    public function down(): void
    {
        Schema::table('aylik_puantaj_parametreleri', function (Blueprint $table) {
            $table->dropColumn('standart_ay_gunu');
        });
    }
};
