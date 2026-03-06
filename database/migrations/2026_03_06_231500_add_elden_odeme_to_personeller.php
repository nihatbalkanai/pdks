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
            $table->decimal('elden_odeme', 12, 2)->nullable()->default(0)->after('aylik_ucret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn('elden_odeme');
        });
    }
};
