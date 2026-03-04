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
            $table->text('notlar')->nullable()->after('durum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn('notlar');
        });
    }
};
