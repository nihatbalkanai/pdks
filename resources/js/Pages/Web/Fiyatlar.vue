<script setup>
import { Head, Link } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';

const props = defineProps({ paketler: Array });

const limitLabel = (v) => v == 0 ? 'Sınırsız' : v;
</script>

<template>
    <Head title="Fiyatlar - PDKSPro" />
    <WebLayout>
        <section class="page-hero">
            <div class="hero-inner">
                <span class="page-badge">Fiyatlandırma</span>
                <h1>İşletmenize Uygun <span class="gradient-text">Planı Seçin</span></h1>
                <p>Şeffaf fiyatlandırma, gizli ücret yok. İhtiyacınıza göre ölçeklendirin.</p>
            </div>
        </section>

        <section class="pricing-section">
            <div class="pricing-container">
                <div class="pricing-grid">
                    <div v-for="p in paketler" :key="p.id" class="price-card" :class="{ popular: p.kod === 'pro' }">
                        <div v-if="p.kod === 'pro'" class="popular-badge">En Popüler</div>
                        <h3 class="price-name" :style="{ color: p.renk }">{{ p.ad }}</h3>
                        <p class="price-desc">{{ p.aciklama }}</p>
                        <div class="price-amount">
                            <span class="currency">₺</span>
                            <span class="amount">{{ Number(p.aylik_fiyat).toLocaleString('tr-TR') }}</span>
                            <span class="period">/ay</span>
                        </div>
                        <div class="price-yearly">₺{{ Number(p.yillik_fiyat).toLocaleString('tr-TR') }} /yıl ile %17 tasarruf</div>

                        <div class="price-limits">
                            <div class="limit-row"><span>Personel</span><strong>{{ limitLabel(p.max_personel) }}</strong></div>
                            <div class="limit-row"><span>Kullanıcı</span><strong>{{ limitLabel(p.max_kullanici) }}</strong></div>
                            <div class="limit-row"><span>Cihaz</span><strong>{{ limitLabel(p.max_cihaz) }}</strong></div>
                        </div>

                        <ul class="price-features">
                            <li v-for="(oz, i) in p.ozellikler" :key="i"><span class="check">✓</span> {{ oz }}</li>
                        </ul>

                        <Link :href="route('login')" class="price-btn" :class="{ primary: p.kod === 'pro' }">
                            {{ p.aylik_fiyat == 0 ? 'Ücretsiz Başla' : 'Planı Seç' }}
                        </Link>
                    </div>
                </div>

                <div class="faq-section">
                    <h2 class="faq-title">Sıkça Sorulan Sorular</h2>
                    <div class="faq-grid">
                        <div class="faq-item" v-for="faq in [
                            { q: 'Ücretsiz plan ne kadar süre geçerli?', a: 'Ücretsiz plan süresizdir. 10 personele kadar tüm temel özellikleri kullanabilirsiniz.' },
                            { q: 'Paket yükseltme nasıl yapılır?', a: 'Hesabınızdan istediğiniz zaman paket yükseltme yapabilirsiniz. Fark ücreti gün bazında hesaplanır.' },
                            { q: 'İptal etmek isterse ne olur?', a: 'İstediğiniz zaman iptal edebilirsiniz. Kalan süreniz sonuna kadar hizmet devam eder.' },
                            { q: 'Kurulum desteği var mı?', a: 'Evet, tüm paketlerde uzaktan kurulum desteği ücretsizdir. Enterprise pakette yerinde kurulum yapılır.' },
                        ]" :key="faq.q">
                            <h4>{{ faq.q }}</h4>
                            <p>{{ faq.a }}</p>
                        </div>
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

.pricing-section { padding: 80px 0; background: white; }
.pricing-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.pricing-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 80px; }

.price-card { padding: 32px 24px; border: 1px solid #e5e7eb; border-radius: 20px; position: relative; transition: all 0.3s; display: flex; flex-direction: column; }
.price-card:hover { border-color: #c7d2fe; box-shadow: 0 10px 40px rgba(99,102,241,0.1); }
.price-card.popular { border-color: #6366f1; box-shadow: 0 10px 40px rgba(99,102,241,0.15); }
.popular-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 4px 16px; border-radius: 100px; font-size: 11px; font-weight: 700; white-space: nowrap; }

.price-name { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
.price-desc { font-size: 13px; color: #6b7280; margin-bottom: 20px; line-height: 1.5; min-height: 40px; }
.price-amount { margin-bottom: 4px; }
.currency { font-size: 20px; font-weight: 700; color: #1e1b4b; vertical-align: top; }
.amount { font-size: 48px; font-weight: 900; color: #1e1b4b; line-height: 1; }
.period { font-size: 14px; color: #9ca3af; }
.price-yearly { font-size: 12px; color: #6366f1; margin-bottom: 20px; }

.price-limits { background: #f9fafb; border-radius: 12px; padding: 14px; margin-bottom: 20px; }
.limit-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #6b7280; }
.limit-row strong { color: #1e1b4b; }

.price-features { list-style: none; padding: 0; margin: 0 0 24px; flex: 1; }
.price-features li { font-size: 13px; color: #4b5563; padding: 5px 0; display: flex; align-items: center; gap: 8px; }
.check { color: #22c55e; font-weight: 700; }

.price-btn { display: block; text-align: center; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; border: 1px solid #e5e7eb; color: #374151; transition: all 0.3s; }
.price-btn:hover { border-color: #6366f1; color: #6366f1; }
.price-btn.primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.price-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }

.faq-title { font-size: 30px; font-weight: 800; color: #1e1b4b; text-align: center; margin-bottom: 40px; }
.faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.faq-item { padding: 24px; border: 1px solid #f3f4f6; border-radius: 14px; }
.faq-item h4 { font-size: 15px; font-weight: 700; color: #1e1b4b; margin-bottom: 8px; }
.faq-item p { font-size: 13px; color: #6b7280; line-height: 1.6; }

@media (max-width: 768px) {
    .page-hero h1 { font-size: 30px; }
    .pricing-grid { grid-template-columns: 1fr; }
    .faq-grid { grid-template-columns: 1fr; }
}
</style>
