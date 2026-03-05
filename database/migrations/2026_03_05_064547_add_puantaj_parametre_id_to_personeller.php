<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->unsignedBigInteger('puantaj_parametre_id')->nullable()->after('servis_id')->index();
            $table->foreign('puantaj_parametre_id')->references('id')->on('gunluk_puantaj_parametreleri')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropForeign(['puantaj_parametre_id']);
            $table->dropColumn('puantaj_parametre_id');
        });
    }
};
