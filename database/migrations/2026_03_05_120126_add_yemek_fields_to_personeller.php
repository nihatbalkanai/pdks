<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->enum('yemek_tipi', ['kart', 'ucret'])->nullable()->after('acil_kisi_telefonu')->comment('Yemek kartı veya ücret');
            $table->string('yemek_kart_no', 50)->nullable()->after('yemek_tipi')->comment('Yemek ticket kart numarası');
            $table->decimal('yemek_ucreti', 10, 2)->nullable()->after('yemek_kart_no')->comment('Günlük yemek ücreti (₺)');
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn(['yemek_tipi', 'yemek_kart_no', 'yemek_ucreti']);
        });
    }
};
