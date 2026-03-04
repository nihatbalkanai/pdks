# PDKS - Personel Devam Kontrol Sistemi

## 🚀 Sunucu Kurulumu

### Gereksinimler
- PHP 8.2+
- MySQL 8.0+ (utf8mb4)
- Composer
- Node.js 18+ & NPM
- Apache/Nginx

### 1. Projeyi Klonla
```bash
git clone https://github.com/nihatbalkanai/pdks.git
cd pdks
```

### 2. Bağımlılıkları Kur
```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

### 3. Ortam Dosyasını Ayarla
```bash
cp .env.example .env
php artisan key:generate
```
`.env` dosyasında veritabanı bilgilerini düzenleyin:
```
DB_DATABASE=pdks
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 4. Veritabanını Yükle

**Yöntem A: Yedeği İçe Aktar (Önerilen - Verilerle birlikte)**
```bash
mysql -u root -p --default-character-set=utf8mb4 pdks < database/backup/pdks_backup.sql
```

**Yöntem B: Sıfırdan Oluştur**
```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=DemoKullanicilarSeeder
php artisan db:seed --class=DemoTanimKodlariSeeder
```

### 5. Dizin İzinleri
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 6. Cache & Optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 7. Cron (Zamanlanmış Bildirimler)
```bash
* * * * * cd /path-to-pdks && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔐 Demo Giriş Bilgileri

| E-Posta | Şifre | Rol |
|---|---|---|
| admin@admin.com | password | Yönetici |
| kullanici@demo.com | 123456 | Kullanıcı |
| muhasebe@demo.com | 123456 | Muhasebe |
| ik@demo.com | 123456 | İK |
| izleyici@demo.com | 123456 | İzleyici |

---

## 📋 Özellikler
- Personel kartları & yönetimi
- PDKS cihaz entegrasyonu
- Toplu işlemler (maaş, izin, avans, prim, AGİ)
- Rol bazlı yetkilendirme (5 rol)
- Mail & SMS bildirim sistemi
- Zamanlanmış bildirimler
- Tanım kodları (şirket, departman, bölüm, ödeme, servis)
- Raporlama
- Dashboard
