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
        Schema::create('mesaj_ayarlari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->enum('kanal', ['sms', 'email']);
            $table->string('api_key')->nullable();
            $table->json('sablonlar')->nullable();
            $table->boolean('durum')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['firma_id', 'kanal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesaj_ayarlari');
    }
};
