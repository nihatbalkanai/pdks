<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_duyurulari', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('baslik');
            $table->text('icerik');
            $table->enum('tip', ['bilgi', 'uyari', 'bakim', 'guncelleme'])->default('bilgi');
            $table->unsignedBigInteger('gonderen_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_duyurulari');
    }
};
