# Laravel + Vue 3 (Inertia) + PSDK Geliştirme Standartları

Sen bu projenin Baş Mimarı (Lead Architect) rolündesin. 10.000 firmaya hizmet verecek bu sistemde aşağıdaki kurallar senin "ana yasan"dır:

### 1. Dil ve İsimlendirme Standartları (Türkçe)
- **Veritabanı:** Tablo isimleri Türkçe ve çoğul (`kullanicilar`, `firmalar`, `cihaz_verileri`). Sütun isimleri Türkçe ve snake_case (`olusturma_tarihi`, `firma_adi`).
- **Kod İçi Yorumlar:** Tüm yorum satırları ve dokümantasyon (DocBlocks) **tamamen Türkçe** olmalıdır.
- **Modeller:** Model isimleri İngilizce (Laravel standardı için: `User`, `Company`) olabilir ancak içindeki `$table` tanımı mutlaka Türkçe tabloya işaret etmelidir.

### 2. Vue 3 & Inertia.js Yapısı
- **Teknoloji:** Vue 3 (Composition API / <script setup>) ve Inertia.js kullanılacaktır.
- **Layouts:** Tüm sayfalar `resources/js/Layouts/AuthenticatedLayout.vue` dosyasını kullanmalıdır. Header, Footer ve Sidebar bu Layout içinde sabitlenmelidir.
- **Bileşenler (Components):** Tekrar eden UI elemanları (Tablolar, Butonlar, Modallar) `resources/js/Components/` altında global bileşenler olarak tasarlanmalıdır.
- **State Management:** Veriler prop olarak Inertia üzerinden gönderilmeli, karmaşık UI durumları için `reactive` veya `ref` kullanılmalıdır.

### 3. MySQL & Performans (10.000 Firma Odaklı)
- **İndeksleme:** `where`, `orderBy`, `join` ve `foreignKey` olan tüm sütunlara migration dosyasında `.index()` eklenmelidir.
- **Raporlama:** Ağır rapor sorguları asla doğrudan ana tablodan çekilmemelidir. Özet tablolar (Aggregation tables) kullanılmalı ve işlemler `Laravel Queues (Kuyruk)` ile arka planda yapılmalıdır.
- **Eager Loading:** "N+1" hatasını önlemek için tüm ilişkiler `with()` ile yüklenmelidir.

### 4. CSS & Görsel Tutarlılık
- **Global Stil:** Tasarımda Tailwind CSS kullanılmalı; butonlar, giriş alanları ve tablolar için proje genelinde standart sınıflar (Örn: `.ana-buton`, `.ozel-tablo`) belirlenmeli ve tüm sayfalarda aynı görünüm korunmalıdır.
- **Responsive:** Arayüz mobil uyumlu (responsive) olmalıdır.

### 5. PSDK & Dosya Yapısı
- **Service Layer:** İş mantığı Controller'da değil, `app/Services/` klasörü altındaki servis sınıflarında toplanmalıdır.
- **Dizin Hataları:** Dosya yolları asla statik yazılmamalı; `storage_path()`, `public_path()` veya Vite için `asset()` kullanılmalıdır.
- **Veri Güvenliği:** Her sorguda `firma_id` kontrolü zorunludur. Bir firma başka bir firmanın verisine asla erişemez.

## 6. Yüksek Performans ve Ölçeklenebilirlik (10K+ Firma)
- **Sorgu Optimizasyonu:** Asla `SELECT *` kullanılmamalı, sadece gerekli sütunlar çekilmelidir.
- **Eager Loading:** "N+1" problemini önlemek için ilişkili veriler `with()` metodu ile yüklenmelidir.
- **Kuyruk Yönetimi:** Raporlama ve ağır veri işleme işlemleri mutlaka `Laravel Jobs/Queues` ile asenkron yapılmalıdır.
- **Index Kuralı:** Yabancı anahtarlar (`foreign keys`) ve arama kriteri olan tüm alanlar migration dosyasında `index()` olarak tanımlanmalıdır.
- **Özetleme:** Yoğun veri içeren tablolar için yıllık/aylık bazda özet tabloları (Aggregation tables) tasarlanmalıdır.

## 7. MySQL Optimizasyon Kuralları
- **Index Zorunluluğu:** `where`, `orderBy` ve `join` kullanılan tüm sütunlar mutlaka `index()` olarak tanımlanmalıdır.
- **Lazy Loading Yasağı:** Eloquent kullanırken asla `foreach` içinde sorgu atılmamalı (`$user->posts` gibi), her zaman `with()` ile Eager Loading yapılmalıdır.
- **Heavy Queries:** 50.000 satırdan fazla veri dönecek rapor sorguları asla ana "Request" içinde yapılmamalı, `database queue` üzerinden asenkron çalıştırılmalıdır.
- **Storage Engine:** Mutlaka `InnoDB` kullanılmalı ve `charset` olarak `utf8mb4_turkish_ci` seçilmelidir.

## 8. Güvenlik ve Veri İzolasyonu (Multi-tenancy)
- **Firma Ayrımı:** Her sorguda mutlaka `where('firma_id', ...)` kısıtlaması olmalıdır. Verilerin firmalar arasında karışması en büyük hatadır.
- **Global Scope:** Laravel'in `Global Scope` özelliği kullanılarak, modellerin otomatik olarak sadece aktif firmanın verilerini getirmesi sağlanmalıdır.
- **SQL Injection:** Tüm veritabanı işlemleri Eloquent veya Query Builder ile yapılmalı, asla ham (raw) SQL stringleri birleştirilmemelidir.

## 9. Hata Yönetimi ve Loglama (Türkçe)
- **Log Dili:** Hata kayıtları (logs) ve kullanıcıya gösterilen hata mesajları tamamen Türkçe olmalıdır.
- **Try-Catch:** Kritik PSDK veri girişlerinde ve veritabanı işlemlerinde mutlaka `try-catch` blokları kullanılmalı, hata oluştuğunda sistem çökmemeli, hata loglanmalıdır.
- **Özel Exceptionlar:** Uygulamaya özgü hata sınıfları (Örn: `FirmaBulunamadiException`) oluşturulmalıdır.

## 10. Vue 3 ve Inertia.js Standartları (Vanilla JS / jQuery Yasağı)
- **Framework Kullanımı:** DOM manipülasyonu, AJAX istekleri ve olay dinleyicileri (event listeners) için kesinlikle Vanilla JS veya jQuery KULLANILMAMALIDIR. 
- **Veri Akışı:** Tüm işlemler Vue 3 Composition API (`ref`, `reactive`, `computed`) ve Inertia.js üzerinden sağlanmalıdır.
- **AJAX İstekleri:** Tüm form submit, CRUD (ekleme/güncelleme/silme) işlemleri `axios` ile yapılmalıdır. Inertia'nın `useForm` kullanılmamalıdır. Detaylar için aşağıdaki **AJAX Kuralları** bölümüne bakınız.

## 11. Dosya ve Dizin Düzeni
- **Service Layer:** Controller'lar 200 satırı geçmemelidir. Karmaşık iş mantığı `app/Services` klasörü altındaki servis sınıflarına taşınmalıdır.
- **Form Requests:** Validasyon işlemleri Controller içinde değil, `app/Http/Requests` altındaki özel sınıflarda yapılmalıdır.

## 12. Kritik Güvenlik ve Veri Yönetimi
- **UUID Zorunluluğu:** Kullanıcıya veya cihaza gösterilen tüm benzersiz kimlikler (ID) `UUID` formatında olmalıdır.
- **Soft Delete:** Tüm kritik modeller `SoftDeletes` özelliğini kullanmalı, veriler kalıcı olarak silinmemelidir.
- **Dinamik Validasyon:** PSDK üzerinden gelen veriler, sisteme işlenmeden önce sıkı bir `Validation` (doğrulama) aşamasından geçirilmelidir.
- **API Güvenliği:** API isteklerinde mutlaka `Sanctum` veya `Passport` ile token bazlı yetkilendirme yapılmalıdır.

# Laravel 12 + Vue 3 (Inertia) + PDKS SaaS Standartları

Bu proje, 10.000 firma ölçeğinde çalışan, yüksek trafikli bir SaaS platformudur. Sen bu sistemin Baş Mimarı olarak aşağıdaki kurallara %100 uymakla yükümlüsün:

### 1. Mimari ve Dil (Türkçe Focus)
- **Veritabanı:** Tablo isimleri Türkçe ve çoğul (`pdks_kayitlari`, `personeller`). Sütunlar Türkçe ve snake_case (`olusturma_tarihi`).
- **Kod İçi Yorumlar:** Tüm yorum satırları ve teknik dökümantasyon **tamamen Türkçe** olmalıdır.
- **Service Layer:** Controller'lar sadece isteği karşılar. İş mantığı, SMS/E-posta gönderimi ve ağır veritabanı işlemleri `app/Services/` klasörü altındaki sınıflarda yapılmalıdır.

### 2. Performans ve "Donmama" Kuralları
- **Özet Tablo (Summary):** 10.000 firma sorgusunda ana tabloyu yormamak için raporlar mutlaka `pdks_gunluk_ozetler` tablosundan çekilmelidir.
- **Redis & Caching:** Firma ayarları ve cihaz durumları gibi sık erişilen veriler Redis ile önbelleğe alınmalıdır.
- **Queues (Kuyruklar):** SMS, E-posta gönderimi ve Excel/PDF rapor oluşturma işlemleri mutlaka arka planda (`Laravel Queues`) yapılmalıdır.
- **İndeksleme:** Tüm `firma_id` ve `tarih` sütunları üzerinde `index()` veya `composite index` tanımlanmalıdır.

### 3. SaaS Güvenliği ve İzolasyon
- **Multi-tenancy:** Her sorguda `firma_id` kontrolü zorunludur. `FirmaKapsami` (Global Scope) ile veriler birbirinden izole edilmelidir.
- **UUID:** Kullanıcıya veya API'ye açılan tüm ID'ler `UUID` olmalıdır.
- **Audit Logs:** Kritik işlemler (silme, yetki değişimi) `sistem_loglari` tablosuna IP adresiyle birlikte kaydedilmelidir.

### 4. Vue 3 & Frontend Düzeni
- **Teknoloji:** Vue 3 (Composition API / <script setup>) ve Inertia.js.
- **Bileşenler:** `resources/js/Components/` altında tekrar kullanılabilir, Tailwind tabanlı ve mobil uyumlu bileşenler geliştirilmelidir.
- **Anlık Veri:** Dashboard güncellemeleri WebSocket (Laravel Reverb) ile sayfa yenilenmeden yapılmalıdır.

### 5. API ve Donanım (PDKS)
- **Doğrulama:** Cihazların API erişimi `Laravel Sanctum` token sistemiyle korunmalıdır.
- **Hız Sınırlama:** API uç noktalarında `Rate Limiting` uygulanarak sistemin DDOS veya hatalı cihaz isteklerine karşı korunması sağlanmalıdır.

**Alertler Uyarılar:** uyarılar ve bildirimler için swettalert kullanılacak.

---

## 13. AJAX Kuralları (Sayfa Yenilenmeden İşlem)

> **ÖNEMLİ:** Bu projede tüm CRUD (ekleme, güncelleme, silme) işlemleri **sayfa yenilenmeden** çalışmalıdır.
> Eğer yeni oluşturulan veya düzenlenen bir sayfada Inertia `useForm` + sayfa yenilemeli yaklaşım tespit edilirse, **derhal axios + yerel state yaklaşımına çevrilmelidir.**

### Frontend Kuralları:
- **useForm YASAK:** Inertia'nın `useForm().post/put/delete()` kullanılmamalıdır. Bunun yerine `axios.post/put/delete()` kullanılmalıdır.
- **reactive form:** Form verileri `reactive({...})` ile tanımlanmalıdır (`useForm` yerine).
- **Yerel liste:** Sayfa verileri `ref([...props.liste])` ile yerel bir array'e kopyalanmalı, CRUD sonrası bu array güncellenerek UI anlık değişmelidir.
- **axios import:** Her Vue dosyasında `import axios from 'axios';` olmalıdır.
- **Bildirimler:** İşlem sonrası `Swal.fire({ toast: true, ... })` ile kullanıcıya bilgi verilmelidir.
- **Hata yakalama:** Her axios çağrısı `try/catch` veya `.catch()` ile sarılmalı, hata mesajı `Swal.fire('Hata', ...)` ile gösterilmelidir.

### Backend Kuralları:
- **JSON Response:** Controller'lar asla `redirect()->back()` veya `redirect()->route()` döndürmemelidir. Bunun yerine `response()->json([...])` döndürmelidir.
- **Başarı yanıtı:** `return response()->json(['success' => true, 'item' => $kayit]);` — yeni/güncel kayıt döndürülmeli.
- **Silme yanıtı:** `return response()->json(['success' => true]);`
- **Hata yanıtı:** `return response()->json(['success' => false, 'message' => 'Hata mesajı'], 422);`

### Örnek Vue Pattern:
```javascript
// ❌ YANLIŞ (eski yöntem — sayfa yenilenir)
const form = useForm({ ad: '', soyad: '' });
form.post(route('personel.store'), {
    onSuccess: () => { /* sayfa zaten yenilendi */ }
});

// ✅ DOĞRU (yeni yöntem — sayfa yenilenmez)
const form = reactive({ ad: '', soyad: '' });
const liste = ref([...props.personeller]);
const kaydet = async () => {
    try {
        const res = await axios.post(route('personel.store'), { ...form });
        liste.value.push(res.data.item); // UI anlık güncellenir
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Eklendi', showConfirmButton: false, timer: 1200 });
    } catch (e) {
        Swal.fire('Hata', e.response?.data?.message || 'Hata oluştu', 'error');
    }
};
```

### İstisna:
- **Sayfa navigasyonu** (router.visit, Link) Inertia ile kalabilir — bunlar zaten AJAX değildir, sayfa geçişidir.
- **Auth sayfaları** (Login, Register, ForgotPassword vb.) Inertia useForm ile kalabilir — bunlar tek seferlik formlardır.
- **Filtre/arama** işlemleri `router.get()` ile yapılabilir (`preserveState: true` ile).