# PDKS — Sunucu Kurulum Rehberi

## Gereksinimler
- PHP >= 8.3
- MySQL >= 8.0
- Node.js >= 18 (build için)
- Composer

---

## 1. Kodu İndir

```bash
git clone https://github.com/nihatbalkanai/pdks.git
cd pdks
```

## 2. Bağımlılıkları Yükle

```bash
composer install --no-dev --optimize-autoloader
```

## 3. .env Dosyasını Ayarla

```bash
cp .env.example .env
# .env dosyasını düzenle:
# APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD, OPENAI_API_KEY
nano .env
```

## 4. Uygulama Key Oluştur

```bash
php artisan key:generate
```

## 5. Veritabanı Oluştur ve İçe Aktar

```bash
mysql -u root -p -e "CREATE DATABASE pdks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p --default-character-set=utf8mb4 pdks < database/backup/pdks_backup.sql
```

## 6. Storage Link

```bash
php artisan storage:link
```

## 7. Cache Temizle

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 8. Frontend Build (opsiyonel — public/build yoksa)

```bash
npm install
npm run build
```

## 9. Nginx Örnek Yapılandırma

```nginx
server {
    listen 80;
    server_name pdks.sizinsite.com;
    root /var/www/pdks/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 10. İzinler (Linux)

```bash
chown -R www-data:www-data /var/www/pdks
chmod -R 755 /var/www/pdks/storage
chmod -R 755 /var/www/pdks/bootstrap/cache
```

---

## Güncelleme (sonraki deploy)

```bash
git pull
composer install --no-dev
php artisan migrate
php artisan config:cache && php artisan route:cache
```
