<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->unsignedBigInteger('vardiya_id')->nullable()->after('aylik_puantaj_parametre_id');
            $table->foreign('vardiya_id')->references('id')->on('vardiyalar')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropForeign(['vardiya_id']);
            $table->dropColumn('vardiya_id');
        });
    }
};
