<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('personeller', function (Blueprint $table) {
            if (!Schema::hasColumn('personeller', 'mobil_sifre')) {
                $table->string('mobil_sifre', 255)->nullable()->after('yol_parasi');
            }
        });
    }
    public function down(): void {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn('mobil_sifre');
        });
    }
};
