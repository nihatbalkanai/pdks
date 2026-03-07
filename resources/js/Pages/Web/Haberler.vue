<script setup>
import { Head } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';

const haberler = [
    { tarih: '2026-03-05', kategori: 'Güncelleme', baslik: 'Super Admin Paneli v2.5 Yayında', ozet: 'Paket yönetimi, destek bilet sistemi, aktivite logları ve firma oluşturma özelliği eklendi.', renk: '#6366f1' },
    { tarih: '2026-02-28', kategori: 'Yeni Özellik', baslik: 'Maaş Raporları Modülü', ozet: 'Genel, grup bazlı ve kişi bazlı maaş ekstresi ile maaş pusulası raporları artık kullanılabilir.', renk: '#22c55e' },
    { tarih: '2026-02-15', kategori: 'Güncelleme', baslik: 'SGK Rapor Kuralı Güncellendi', ozet: 'İlk 2 gün firma sorumluluğu, sonraki günler SGK kapsamı kuralı puantaj hesaplamalarına eklendi.', renk: '#6366f1' },
    { tarih: '2026-02-01', kategori: 'Güvenlik', baslik: 'Rol Bazlı Erişim Kontrolü', ozet: 'Admin, yönetici, muhasebe ve kullanıcı rolleri ile detaylı yetkilendirme sistemi güncellendi.', renk: '#ef4444' },
    { tarih: '2026-01-20', kategori: 'Yeni Özellik', baslik: 'Servis Yol Atama Modülü', ozet: 'Personel servis güzergahları ve maliyet takibi için yeni modül eklendi.', renk: '#22c55e' },
    { tarih: '2026-01-10', kategori: 'Bakım', baslik: 'Planlı Sistem Bakımı Tamamlandı', ozet: 'Veritabanı optimizasyonu ve performans iyileştirmeleri başarıyla uygulandı.', renk: '#f59e0b' },
];
</script>

<template>
    <Head title="Haberler - PDKSPro" />
    <WebLayout>
        <section class="page-hero">
            <div class="hero-inner">
                <span class="page-badge">Haberler</span>
                <h1>Güncellemeler & <span class="gradient-text">Duyurular</span></h1>
                <p>PDKSPro'nun son gelişmelerini ve yeni özelliklerini takip edin.</p>
            </div>
        </section>

        <section class="news-section">
            <div class="news-container">
                <div class="news-grid">
                    <article v-for="(h, i) in haberler" :key="i" class="news-card">
                        <div class="news-meta">
                            <span class="news-badge" :style="{ background: h.renk + '15', color: h.renk }">{{ h.kategori }}</span>
                            <span class="news-date">{{ new Date(h.tarih).toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
                        </div>
                        <h3>{{ h.baslik }}</h3>
                        <p>{{ h.ozet }}</p>
                    </article>
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

.news-section { padding: 80px 0; background: white; }
.news-container { max-width: 900px; margin: 0 auto; padding: 0 24px; }
.news-grid { display: flex; flex-direction: column; gap: 24px; }
.news-card { padding: 28px; border: 1px solid #f3f4f6; border-radius: 16px; transition: all 0.3s; }
.news-card:hover { border-color: #e0e7ff; box-shadow: 0 8px 25px rgba(99,102,241,0.08); }
.news-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.news-badge { padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.news-date { font-size: 12px; color: #9ca3af; }
.news-card h3 { font-size: 18px; font-weight: 700; color: #1e1b4b; margin-bottom: 8px; }
.news-card p { font-size: 14px; color: #6b7280; line-height: 1.6; }

@media (max-width: 768px) { .page-hero h1 { font-size: 30px; } }
</style>
