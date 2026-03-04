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
        Schema::create('personel_prim_kazanclari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->foreignId('personel_id')->constrained('personeller')->onDelete('cascade');
            $table->date('tarih');
            $table->decimal('tutar', 15, 2);
            $table->string('aciklama')->nullable();
            $table->string('bordro_alani')->nullable();
            $table->timestamps();

            $table->index(['firma_id', 'personel_id', 'tarih']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personel_prim_kazanclari');
    }
};
