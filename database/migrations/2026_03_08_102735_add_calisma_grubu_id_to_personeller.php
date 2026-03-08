<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->unsignedBigInteger('calisma_grubu_id')->nullable()->after('vardiya_id');
            $table->foreign('calisma_grubu_id')->references('id')->on('calisma_gruplari')->nullOnDelete();
            $table->index('calisma_grubu_id');
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropForeign(['calisma_grubu_id']);
            $table->dropColumn('calisma_grubu_id');
        });
    }
};
