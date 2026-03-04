<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bildirim_loglari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->nullable()->constrained('firmalar')->onDelete('cascade');
            $table->unsignedBigInteger('personel_id')->nullable();
            $table->enum('kanal', ['sms', 'email']);
            $table->string('alici');
            $table->string('konu')->nullable();
            $table->text('mesaj');
            $table->string('durum')->default('bekliyor');
            $table->timestamps();

            $table->index(['firma_id', 'kanal', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bildirim_loglari');
    }
};
