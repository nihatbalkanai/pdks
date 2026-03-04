<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanim_kodlari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('firma_id')->constrained('firmalar')->onDelete('cascade');
            $table->string('tip'); // sirket, departman, bolum, odeme, servis
            $table->string('kod', 50);
            $table->string('aciklama');
            $table->boolean('durum')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['firma_id', 'tip']);
            $table->unique(['firma_id', 'tip', 'kod']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanim_kodlari');
    }
};
