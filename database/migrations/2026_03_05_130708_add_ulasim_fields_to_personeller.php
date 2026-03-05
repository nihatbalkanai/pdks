<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->enum('ulasim_tipi', ['servis', 'yol_parasi'])->nullable()->after('yemek_ucreti')->comment('Servis hakkı veya yol parası');
            $table->string('servis_plaka', 20)->nullable()->after('ulasim_tipi')->comment('Servis plaka numarası');
            $table->decimal('yol_parasi', 10, 2)->nullable()->after('servis_plaka')->comment('Günlük yol parası (₺)');
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn(['ulasim_tipi', 'servis_plaka', 'yol_parasi']);
        });
    }
};
