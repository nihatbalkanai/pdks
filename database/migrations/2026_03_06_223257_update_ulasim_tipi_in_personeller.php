<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Önce mevcut 'yol_parasi' olanları kaybetmemek için enum tanımına 'yol_parasi' ekliyorum (veya doğrudan update ediyorum)
        // Ancak bu sadece bir tanım değişikliği
        DB::statement("ALTER TABLE personeller MODIFY COLUMN ulasim_tipi ENUM('servis', 'yol_parasi', 'yol_parasi_gunluk', 'yol_parasi_aylik') NULL COMMENT 'Servis hakkı, günlük yol parası veya aylık yol parası'");
        
        // Önceki 'yol_parasi' kayıtlarını yeni istenen türe (örneğin günlük) eşle
        DB::statement("UPDATE personeller SET ulasim_tipi = 'yol_parasi_gunluk' WHERE ulasim_tipi = 'yol_parasi'");
        
        // Enum'u temizle (eski 'yol_parasi' değerini kaldır)
        DB::statement("ALTER TABLE personeller MODIFY COLUMN ulasim_tipi ENUM('servis', 'yol_parasi_gunluk', 'yol_parasi_aylik') NULL COMMENT 'Servis hakkı, günlük veya aylık yol parası'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE personeller MODIFY COLUMN ulasim_tipi ENUM('servis', 'yol_parasi', 'yol_parasi_gunluk', 'yol_parasi_aylik') NULL COMMENT 'Servis hakkı veya yol parası'");
        DB::statement("UPDATE personeller SET ulasim_tipi = 'yol_parasi' WHERE ulasim_tipi IN ('yol_parasi_gunluk', 'yol_parasi_aylik')");
        DB::statement("ALTER TABLE personeller MODIFY COLUMN ulasim_tipi ENUM('servis', 'yol_parasi') NULL COMMENT 'Servis hakkı veya yol parası'");
    }
};
