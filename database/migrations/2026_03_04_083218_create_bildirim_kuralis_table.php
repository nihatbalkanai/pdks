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
        Schema::create('bildirim_kurallari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('kural_tipi'); // geç kaldı, gelmedi vs.
            $table->time('tetikleme_saati');
            $table->string('alici_telefon')->nullable();
            $table->string('alici_eposta')->nullable();
            $table->boolean('durum')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['firma_id', 'tetikleme_saati']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bildirim_kurallari');
    }
};
