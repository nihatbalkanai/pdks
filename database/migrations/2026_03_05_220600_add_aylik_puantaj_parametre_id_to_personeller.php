<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->unsignedBigInteger('aylik_puantaj_parametre_id')->nullable()->after('puantaj_parametre_id');
            $table->index('aylik_puantaj_parametre_id');
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropIndex(['aylik_puantaj_parametre_id']);
            $table->dropColumn('aylik_puantaj_parametre_id');
        });
    }
};
