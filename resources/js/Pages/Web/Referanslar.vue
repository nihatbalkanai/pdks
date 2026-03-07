<script setup>
import { Head } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';

const referanslar = [
    { firma: 'ABC Holding', sektor: 'Üretim', personel: '1.200+', yorum: 'PDKSPro ile puantaj hesaplamalarımız %90 daha hızlı. Artık Excel\'de saatler harcamıyoruz.', kisi: 'Mehmet Yıldız', unvan: 'İK Müdürü', puan: 5 },
    { firma: 'TeknoSoft A.Ş.', sektor: 'Teknoloji', personel: '350+', yorum: 'Çoklu şube yönetimi harika çalışıyor. Tüm ofislerimizi tek panelden yönetiyoruz.', kisi: 'Ayşe Demir', unvan: 'COO', puan: 5 },
    { firma: 'Güven Lojistik', sektor: 'Lojistik', personel: '800+', yorum: 'SGK rapor kuralları, fazla mesai hesaplama... Her şey Türk mevzuatına uygun. Mükemmel!', kisi: 'Ali Kara', unvan: 'Genel Müdür', puan: 5 },
    { firma: 'Metro Gıda', sektor: 'Perakende', personel: '2.500+', yorum: 'Enterprise paketi kullanıyoruz. 7/24 destek gerçekten 7/24! Teknik ekip çok hızlı.', kisi: 'Fatma Aksoy', unvan: 'İK Direktörü', puan: 5 },
    { firma: 'Dijital Ajans', sektor: 'Dijital Pazarlama', personel: '45', yorum: 'Küçük bir ekibiz ama PDKSPro sayesinde İK süreçlerimiz kurumsal şirketler gibi.', kisi: 'Can Özkan', unvan: 'Kurucu', puan: 4 },
    { firma: 'Yeşil Enerji', sektor: 'Enerji', personel: '600+', yorum: 'İzin yönetimi ve kıdem bazlı hak ediş hesaplama tam istediğimiz gibi çalışıyor.', kisi: 'Zehra Tunç', unvan: 'İK Uzmanı', puan: 5 },
];
</script>

<template>
    <Head title="Referanslar - PDKSPro" />
    <WebLayout>
        <section class="page-hero">
            <div class="hero-inner">
                <span class="page-badge">Referanslar</span>
                <h1>Müşterilerimiz <span class="gradient-text">Ne Diyor?</span></h1>
                <p>Yüzlerce firma PDKSPro'yu tercih ediyor. İşte bazı müşterilerimizin deneyimleri.</p>
            </div>
        </section>

        <section class="ref-section">
            <div class="ref-container">
                <div class="ref-grid">
                    <div v-for="(r, i) in referanslar" :key="i" class="ref-card">
                        <div class="ref-stars">
                            <span v-for="s in r.puan" :key="s">⭐</span>
                        </div>
                        <p class="ref-yorum">"{{ r.yorum }}"</p>
                        <div class="ref-kisi">
                            <div class="ref-avatar">{{ r.kisi[0] }}</div>
                            <div>
                                <h4>{{ r.kisi }}</h4>
                                <p>{{ r.unvan }} · {{ r.firma }}</p>
                            </div>
                        </div>
                        <div class="ref-meta">
                            <span>{{ r.sektor }}</span>
                            <span>{{ r.personel }} personel</span>
                        </div>
                    </div>
                </div>

                <div class="logos-section">
                    <h3>Güvenen Firmalar</h3>
                    <div class="logos-grid">
                        <div v-for="f in ['ABC Holding', 'TeknoSoft', 'Güven Lojistik', 'Metro Gıda', 'Dijital Ajans', 'Yeşil Enerji', 'Ak Sigorta', 'Bayrak İnşaat']" :key="f" class="logo-item">{{ f }}</div>
                    </div>
                </div>
            </div>
        </section>
    </WebLayout>
</template>

<style scoped>
.page-hero { padding: 140px 24px 80px; text-align: center; background: linear-gradient(135deg, #eef2ff, #e0e7ff, #ddd6fe); }
.hero-inner { max-width: 700px; margin: 0 auto; }
.page-badge { display: inline-block; padding: 4px 14px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 100px; font-size: 12px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.page-hero h1 { font-size: 44px; font-weight: 900; color: #1e1b4b; margin-bottom: 16px; line-height: 1.1; }
.gradient-text { background: linear-gradient(135deg, #6366f1, #a855f7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.page-hero p { font-size: 17px; color: #6b7280; }

.ref-section { padding: 80px 0; background: white; }
.ref-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.ref-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 80px; }
.ref-card { padding: 28px; border: 1px solid #f3f4f6; border-radius: 16px; transition: all 0.3s; }
.ref-card:hover { border-color: #e0e7ff; box-shadow: 0 8px 30px rgba(99,102,241,0.08); }
.ref-stars { margin-bottom: 14px; font-size: 14px; }
.ref-yorum { font-size: 14px; color: #374151; line-height: 1.7; margin-bottom: 20px; font-style: italic; }
.ref-kisi { display: flex; gap: 12px; align-items: center; margin-bottom: 14px; }
.ref-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; }
.ref-kisi h4 { font-size: 14px; font-weight: 700; color: #1e1b4b; }
.ref-kisi p { font-size: 12px; color: #9ca3af; }
.ref-meta { display: flex; gap: 12px; }
.ref-meta span { font-size: 11px; background: #f3f4f6; padding: 3px 10px; border-radius: 6px; color: #6b7280; }

.logos-section { text-align: center; }
.logos-section h3 { font-size: 14px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; }
.logos-grid { display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; }
.logo-item { padding: 14px 28px; background: #f9fafb; border-radius: 10px; font-size: 14px; font-weight: 700; color: #9ca3af; }

@media (max-width: 768px) {
    .page-hero h1 { font-size: 30px; }
    .ref-grid { grid-template-columns: 1fr; }
}
</style>
