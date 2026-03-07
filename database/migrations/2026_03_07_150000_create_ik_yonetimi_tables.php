<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // İzin Talepleri
        Schema::create('izin_talepleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->unsignedBigInteger('talep_eden_id'); // Kullanıcı
            $table->unsignedBigInteger('onaylayan_id')->nullable();
            $table->string('izin_turu'); // yillik, hastalik, ucretsiz, mazeret, dogum, evlilik, olum, vb.
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi');
            $table->decimal('gun_sayisi', 5, 1);
            $table->text('aciklama')->nullable();
            $table->enum('durum', ['beklemede', 'onaylandi', 'reddedildi', 'iptal'])->default('beklemede');
            $table->text('red_nedeni')->nullable();
            $table->timestamp('onay_tarihi')->nullable();
            $table->timestamps();
            $table->index(['firma_id', 'personel_id']);
            $table->index('durum');
        });

        // Performans Değerlendirme
        Schema::create('performans_degerlendirmeleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->unsignedBigInteger('degerlendiren_id');
            $table->string('donem'); // 2026-Q1, 2026-H1, 2026
            $table->enum('donem_tipi', ['aylik', 'ceyrek', 'yillik'])->default('ceyrek');
            $table->unsignedTinyInteger('is_kalitesi')->default(0); // 1-10
            $table->unsignedTinyInteger('verimlilik')->default(0);
            $table->unsignedTinyInteger('iletisim')->default(0);
            $table->unsignedTinyInteger('sorumluluk')->default(0);
            $table->unsignedTinyInteger('takim_calismasi')->default(0);
            $table->unsignedTinyInteger('liderlik')->default(0);
            $table->unsignedTinyInteger('yaraticilik')->default(0);
            $table->unsignedTinyInteger('devam_durum')->default(0);
            $table->decimal('genel_puan', 4, 2)->default(0);
            $table->text('guclu_yonler')->nullable();
            $table->text('gelistirilecek_yonler')->nullable();
            $table->text('hedefler')->nullable();
            $table->text('notlar')->nullable();
            $table->timestamps();
            $table->index(['firma_id', 'personel_id']);
        });

        // Eğitim Kayıtları
        Schema::create('egitim_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->string('egitim_adi');
            $table->string('egitim_turu'); // ic, dis, online, sertifika
            $table->string('kurum')->nullable(); // Eğitim veren kurum
            $table->date('baslangic_tarihi');
            $table->date('bitis_tarihi')->nullable();
            $table->unsignedSmallInteger('sure_saat')->nullable();
            $table->string('sertifika_no')->nullable();
            $table->date('sertifika_gecerlilik')->nullable();
            $table->enum('durum', ['planlanmis', 'devam_ediyor', 'tamamlandi', 'iptal'])->default('planlanmis');
            $table->text('notlar')->nullable();
            $table->timestamps();
            $table->index(['firma_id', 'personel_id']);
        });

        // Disiplin Kayıtları
        Schema::create('disiplin_kayitlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('firma_id');
            $table->unsignedBigInteger('personel_id');
            $table->unsignedBigInteger('kaydeden_id');
            $table->enum('tur', ['sozlu_uyari', 'yazili_uyari', 'kinama', 'ucret_kesintisi', 'fesih_uyarisi', 'diger']);
            $table->date('olay_tarihi');
            $table->text('olay_aciklamasi');
            $table->text('alinan_onlem')->nullable();
            $table->boolean('personel_bilgilendirildi')->default(false);
            $table->date('bilgilendirme_tarihi')->nullable();
            $table->text('notlar')->nullable();
            $table->timestamps();
            $table->index(['firma_id', 'personel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disiplin_kayitlari');
        Schema::dropIfExists('egitim_kayitlari');
        Schema::dropIfExists('performans_degerlendirmeleri');
        Schema::dropIfExists('izin_talepleri');
    }
};
