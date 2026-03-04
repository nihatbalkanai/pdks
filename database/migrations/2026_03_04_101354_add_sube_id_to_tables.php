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
            $table->foreignId('sube_id')->nullable()->after('firma_id')->constrained('subeler')->nullOnDelete();
            $table->foreignId('servis_id')->nullable()->after('sube_id')->constrained('servisler')->nullOnDelete();
            
            // Performans için composite indexler (Veri İzolasyonu ve Performans kuralı)
            $table->index(['firma_id', 'sube_id']);
            $table->index(['firma_id', 'servis_id']);
        });

        Schema::table('pdks_cihazlari', function (Blueprint $table) {
            $table->foreignId('sube_id')->nullable()->after('firma_id')->constrained('subeler')->nullOnDelete();
            $table->index(['firma_id', 'sube_id']);
        });
        
        Schema::table('kullanicilar', function (Blueprint $table) {
            $table->foreignId('sube_id')->nullable()->after('firma_id')->constrained('subeler')->nullOnDelete();
            $table->index(['firma_id', 'sube_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kullanicilar', function (Blueprint $table) {
            $table->dropForeign(['sube_id']);
            $table->dropIndex(['firma_id', 'sube_id']);
            $table->dropColumn('sube_id');
        });

        Schema::table('pdks_cihazlari', function (Blueprint $table) {
            $table->dropForeign(['sube_id']);
            $table->dropIndex(['firma_id', 'sube_id']);
            $table->dropColumn('sube_id');
        });

        Schema::table('personeller', function (Blueprint $table) {
            $table->dropForeign(['sube_id']);
            $table->dropForeign(['servis_id']);
            $table->dropIndex(['firma_id', 'sube_id']);
            $table->dropIndex(['firma_id', 'servis_id']);
            $table->dropColumn(['sube_id', 'servis_id']);
        });
    }
};
