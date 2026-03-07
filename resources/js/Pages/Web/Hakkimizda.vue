<script setup>
import { Head } from '@inertiajs/vue3';
import WebLayout from '@/Layouts/WebLayout.vue';
</script>

<template>
    <Head title="Hakkımızda - PDKSPro" />
    <WebLayout>
        <section class="page-hero">
            <div class="hero-inner">
                <span class="page-badge">Hakkımızda</span>
                <h1>Türkiye'nin Öncü <span class="gradient-text">PDKS Platformu</span></h1>
                <p>2020 yılından bu yana personel yönetimini dijitalleştiren, yüzlerce firmanın güvendiği teknoloji çözümü.</p>
            </div>
        </section>

        <section class="content-section">
            <div class="content-container">
                <div class="story-grid">
                    <div>
                        <h2 class="content-title">Hikayemiz</h2>
                        <p class="content-text">PDKSPro, Türkiye'deki firmaların personel devam kontrol süreçlerindeki zorluklardan doğdu. Kağıt üzerinde tutulan puantaj kayıtları, Excel'de hesaplanan maaşlar ve kaybedilen izin belgeleri... Tüm bu sorunlara dijital bir çözüm sunmak amacıyla 2020 yılında kurulduk.</p>
                        <p class="content-text">Bugün 500'den fazla firma ve 25.000'den fazla personelin takibini başarıyla yönetmekteyiz. Sürekli geliştirilen altyapımız, Türk iş hukukuna tam uyumlu hesaplama motorumuz ve 7/24 destek ekibimiz ile sektörde lider konumdayız.</p>
                    </div>
                    <div class="values-card">
                        <div class="value-item">
                            <span class="value-icon">🎯</span>
                            <div>
                                <h4>Misyonumuz</h4>
                                <p>Her ölçekteki firmaya erişilebilir, güvenilir ve kullanımı kolay personel yönetim çözümleri sunmak.</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <span class="value-icon">🔭</span>
                            <div>
                                <h4>Vizyonumuz</h4>
                                <p>Türkiye'nin en yaygın kullanılan İK teknoloji platformu olmak ve global pazarlara açılmak.</p>
                            </div>
                        </div>
                        <div class="value-item">
                            <span class="value-icon">💡</span>
                            <div>
                                <h4>Değerlerimiz</h4>
                                <p>Güvenilirlik, yenilikçilik, müşteri odaklılık ve sürekli gelişim.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stats-bar">
                    <div class="stat-box"><span class="stat-num">6+</span><span class="stat-txt">Yıllık Tecrübe</span></div>
                    <div class="stat-box"><span class="stat-num">500+</span><span class="stat-txt">Aktif Firma</span></div>
                    <div class="stat-box"><span class="stat-num">25K+</span><span class="stat-txt">Personel Takibi</span></div>
                    <div class="stat-box"><span class="stat-num">%99.9</span><span class="stat-txt">Uptime</span></div>
                </div>

                <div class="team-section">
                    <h2 class="content-title center">Ekibimiz</h2>
                    <p class="content-text center" style="max-width:500px;margin:0 auto 40px">Deneyimli mühendisler ve İK uzmanlarından oluşan ekibimiz, en iyi çözümü sunmak için çalışıyor.</p>
                    <div class="team-grid">
                        <div class="team-card" v-for="member in [
                            { name: 'Ahmet Yılmaz', role: 'Kurucu & CEO', avatar: '👨‍💼' },
                            { name: 'Elif Kaya', role: 'CTO', avatar: '👩‍💻' },
                            { name: 'Mehmet Demir', role: 'Yazılım Müh.', avatar: '👨‍💻' },
                            { name: 'Zeynep Aksoy', role: 'İK Danışman', avatar: '👩‍💼' },
                        ]" :key="member.name">
                            <span class="team-avatar">{{ member.avatar }}</span>
                            <h4>{{ member.name }}</h4>
                            <p>{{ member.role }}</p>
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
.page-hero p { font-size: 17px; color: #6b7280; max-width: 500px; margin: 0 auto; line-height: 1.6; }

.content-section { padding: 80px 0; background: white; }
.content-container { max-width: 1100px; margin: 0 auto; padding: 0 24px; }
.content-title { font-size: 30px; font-weight: 800; color: #1e1b4b; margin-bottom: 16px; }
.content-title.center { text-align: center; }
.content-text { font-size: 15px; color: #6b7280; line-height: 1.8; margin-bottom: 16px; }
.content-text.center { text-align: center; }

.story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 60px; }
.values-card { background: #fafbff; border-radius: 20px; padding: 32px; border: 1px solid #eef2ff; }
.value-item { display: flex; gap: 14px; margin-bottom: 24px; }
.value-item:last-child { margin-bottom: 0; }
.value-icon { font-size: 28px; }
.value-item h4 { font-size: 15px; font-weight: 700; color: #1e1b4b; margin-bottom: 4px; }
.value-item p { font-size: 13px; color: #6b7280; line-height: 1.5; }

.stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 80px; }
.stat-box { text-align: center; padding: 30px; background: linear-gradient(135deg, #f5f3ff, #eef2ff); border-radius: 16px; }
.stat-num { display: block; font-size: 36px; font-weight: 900; color: #6366f1; }
.stat-txt { font-size: 13px; color: #6b7280; margin-top: 4px; }

.team-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
.team-card { text-align: center; padding: 30px 20px; border-radius: 16px; border: 1px solid #f3f4f6; transition: all 0.3s; }
.team-card:hover { border-color: #e0e7ff; box-shadow: 0 8px 25px rgba(99,102,241,0.08); }
.team-avatar { font-size: 48px; display: block; margin-bottom: 12px; }
.team-card h4 { font-size: 15px; font-weight: 700; color: #1e1b4b; }
.team-card p { font-size: 13px; color: #6b7280; margin-top: 2px; }

@media (max-width: 768px) {
    .page-hero h1 { font-size: 30px; }
    .story-grid, .stats-bar, .team-grid { grid-template-columns: 1fr; }
    .stats-bar { grid-template-columns: repeat(2, 1fr); }
}
</style>
