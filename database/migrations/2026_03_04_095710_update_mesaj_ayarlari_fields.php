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
        Schema::table('mesaj_ayarlari', function (Blueprint $table) {
            $table->dropColumn('api_key');
            $table->dropColumn('sablonlar');
            $table->string('api_anahtari')->nullable()->after('kanal');
            $table->string('gonderici_basligi')->nullable()->after('api_anahtari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mesaj_ayarlari', function (Blueprint $table) {
            $table->dropColumn('api_anahtari');
            $table->dropColumn('gonderici_basligi');
            $table->string('api_key')->nullable();
            $table->json('sablonlar')->nullable();
        });
    }
};
