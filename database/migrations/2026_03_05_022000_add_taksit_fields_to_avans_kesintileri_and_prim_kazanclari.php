<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kesintiler ve ek kazançlar tablolarına taksit alanları eklenir.
     */
    public function up(): void
    {
        // Avans/Kesintiler tablosuna taksit alanları
        Schema::table('personel_avans_kesintileri', function (Blueprint $table) {
            $table->string('taksit_grup_id')->nullable()->after('bordro_alani')->comment('Aynı taksit grubundaki kayıtları bağlayan UUID');
            $table->unsignedSmallInteger('taksit_no')->nullable()->after('taksit_grup_id')->comment('Kaçıncı taksit (1,2,3...)');
            $table->unsignedSmallInteger('toplam_taksit')->nullable()->after('taksit_no')->comment('Toplam taksit sayısı');
            $table->decimal('toplam_tutar', 15, 2)->nullable()->after('toplam_taksit')->comment('Taksitlendirilen toplam tutar');

            $table->index('taksit_grup_id');
        });

        // Prim/Kazançlar tablosuna taksit alanları
        Schema::table('personel_prim_kazanclari', function (Blueprint $table) {
            $table->string('taksit_grup_id')->nullable()->after('bordro_alani')->comment('Aynı taksit grubundaki kayıtları bağlayan UUID');
            $table->unsignedSmallInteger('taksit_no')->nullable()->after('taksit_grup_id')->comment('Kaçıncı taksit (1,2,3...)');
            $table->unsignedSmallInteger('toplam_taksit')->nullable()->after('taksit_no')->comment('Toplam taksit sayısı');
            $table->decimal('toplam_tutar', 15, 2)->nullable()->after('toplam_taksit')->comment('Taksitlendirilen toplam tutar');

            $table->index('taksit_grup_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personel_avans_kesintileri', function (Blueprint $table) {
            $table->dropIndex(['taksit_grup_id']);
            $table->dropColumn(['taksit_grup_id', 'taksit_no', 'toplam_taksit', 'toplam_tutar']);
        });

        Schema::table('personel_prim_kazanclari', function (Blueprint $table) {
            $table->dropIndex(['taksit_grup_id']);
            $table->dropColumn(['taksit_grup_id', 'taksit_no', 'toplam_taksit', 'toplam_tutar']);
        });
    }
};
