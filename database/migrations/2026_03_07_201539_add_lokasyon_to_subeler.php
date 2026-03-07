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
        Schema::table('subeler', function (Blueprint $table) {
            $table->decimal('lokasyon_enlem', 10, 7)->nullable()->after('lokasyon')->comment('Şube GPS enlem');
            $table->decimal('lokasyon_boylam', 10, 7)->nullable()->after('lokasyon_enlem')->comment('Şube GPS boylam');
            $table->unsignedSmallInteger('geofence_yaricap')->default(200)->after('lokasyon_boylam')->comment('İzin verilen yarıçap (metre)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subeler', function (Blueprint $table) {
            $table->dropColumn(['lokasyon_enlem', 'lokasyon_boylam', 'geofence_yaricap']);
        });
    }
};
