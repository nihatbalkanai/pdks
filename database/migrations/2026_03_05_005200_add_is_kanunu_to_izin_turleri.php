<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_turleri', function (Blueprint $table) {
            // Türk İş Kanunu kuralları
            $table->boolean('hafta_sonu_haric_mi')->default(false)->after('yillik_izinden_duser_mi')
                ->comment('true ise hafta sonu günleri izin gün sayısından hariç tutulur (Yıllık İzin gibi)');
            $table->boolean('resmi_tatil_haric_mi')->default(false)->after('hafta_sonu_haric_mi')
                ->comment('true ise resmi tatil günleri izin gün sayısından hariç tutulur');
            $table->integer('max_gun')->nullable()->after('resmi_tatil_haric_mi')
                ->comment('Bu izin türü için maksimum gün sayısı (null=sınırsız)');
        });

        // Varsayılan değerleri güncelle — Yıllık İzin için hafta sonu+tatil hariç
        \DB::table('izin_turleri')->where('ad', 'Yıllık İzin')->update([
            'hafta_sonu_haric_mi' => true,
            'resmi_tatil_haric_mi' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('izin_turleri', function (Blueprint $table) {
            $table->dropColumn(['hafta_sonu_haric_mi', 'resmi_tatil_haric_mi', 'max_gun']);
        });
    }
};
