<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->string('email')->nullable()->after('notlar');
            $table->string('telefon')->nullable()->after('email');
            $table->boolean('gec_kalma_bildirimi')->default(false)->after('telefon');
        });
    }

    public function down(): void
    {
        Schema::table('personeller', function (Blueprint $table) {
            $table->dropColumn(['email', 'telefon', 'gec_kalma_bildirimi']);
        });
    }
};
