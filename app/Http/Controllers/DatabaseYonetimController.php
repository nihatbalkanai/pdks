<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class DatabaseYonetimController extends Controller
{
    /**
     * Veritabanı yönetim sayfası
     */
    public function index()
    {
        return Inertia::render('SuperAdmin/DatabaseYonetim', [
            'localDb' => [
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
                'username' => config('database.connections.mysql.username'),
            ],
        ]);
    }

    /**
     * Veritabanına bağlan ve tabloları listele
     */
    public function tablolar(Request $request)
    {
        try {
            $pdo = $this->baglan($request);
            $dbName = $request->database;

            $stmt = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, ENGINE, TABLE_COLLATION, CREATE_TIME, UPDATE_TIME, TABLE_COMMENT 
                FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$dbName}' ORDER BY TABLE_NAME");
            $tablolar = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return response()->json([
                'hata' => false,
                'tablolar' => $tablolar,
                'toplam' => count($tablolar),
            ]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Tablo yapısını göster (kolonlar, indexler)
     */
    public function tabloYapisi(Request $request)
    {
        try {
            $pdo = $this->baglan($request);
            $tablo = $request->tablo;

            // Kolonlar
            $stmt = $pdo->query("DESCRIBE `{$tablo}`");
            $kolonlar = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // İndexler
            $stmt2 = $pdo->query("SHOW INDEX FROM `{$tablo}`");
            $indexler = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

            // Satır sayısı
            $stmt3 = $pdo->query("SELECT COUNT(*) as cnt FROM `{$tablo}`");
            $satirSayisi = $stmt3->fetch(\PDO::FETCH_ASSOC)['cnt'];

            // CREATE TABLE
            $stmt4 = $pdo->query("SHOW CREATE TABLE `{$tablo}`");
            $createSql = $stmt4->fetch(\PDO::FETCH_ASSOC)['Create Table'] ?? '';

            return response()->json([
                'hata' => false,
                'kolonlar' => $kolonlar,
                'indexler' => $indexler,
                'satir_sayisi' => $satirSayisi,
                'create_sql' => $createSql,
            ]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Tablo verilerini getir (sayfalı)
     */
    public function tabloVerileri(Request $request)
    {
        try {
            $pdo = $this->baglan($request);
            $tablo = $request->tablo;
            $sayfa = max(1, (int) ($request->sayfa ?? 1));
            $limit = min(100, max(10, (int) ($request->limit ?? 50)));
            $offset = ($sayfa - 1) * $limit;

            // Toplam
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `{$tablo}`");
            $toplam = $stmt->fetch(\PDO::FETCH_ASSOC)['cnt'];

            // Veriler
            $stmt2 = $pdo->query("SELECT * FROM `{$tablo}` LIMIT {$limit} OFFSET {$offset}");
            $veriler = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

            // Kolon adları
            $kolonlar = $veriler ? array_keys($veriler[0]) : [];

            return response()->json([
                'hata' => false,
                'veriler' => $veriler,
                'kolonlar' => $kolonlar,
                'toplam' => $toplam,
                'sayfa' => $sayfa,
                'limit' => $limit,
                'toplam_sayfa' => ceil($toplam / $limit),
            ]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * SQL sorgusu çalıştır
     */
    public function sorguCalistir(Request $request)
    {
        $request->validate([
            'sql' => 'required|string|max:5000',
        ]);

        try {
            $pdo = $this->baglan($request);
            $sql = trim($request->sql);

            // Güvenlik: Tehlikeli komutları kontrol et
            $tehlikeli = ['DROP DATABASE', 'TRUNCATE', 'DROP TABLE'];
            foreach ($tehlikeli as $t) {
                if (stripos($sql, $t) !== false) {
                    return response()->json(['hata' => true, 'mesaj' => "Güvenlik: '{$t}' komutu engellenmiştir."], 403);
                }
            }

            $isSelect = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql);

            // Yazma işlemleri için şifre doğrulama gerekli
            if (!$isSelect) {
                $sifreHata = $this->sifreDogrula($request);
                if ($sifreHata) return $sifreHata;
            }

            $baslangic = microtime(true);

            if ($isSelect) {
                $stmt = $pdo->query($sql);
                $sonuclar = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $sure = round((microtime(true) - $baslangic) * 1000, 2);

                return response()->json([
                    'hata' => false,
                    'tip' => 'select',
                    'sonuclar' => $sonuclar,
                    'kolonlar' => $sonuclar ? array_keys($sonuclar[0]) : [],
                    'satir_sayisi' => count($sonuclar),
                    'sure_ms' => $sure,
                ]);
            } else {
                $affected = $pdo->exec($sql);
                $sure = round((microtime(true) - $baslangic) * 1000, 2);

                return response()->json([
                    'hata' => false,
                    'tip' => 'exec',
                    'etkilenen_satir' => $affected,
                    'sure_ms' => $sure,
                    'mesaj' => "{$affected} satır etkilendi ({$sure}ms)",
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Tablo oluştur
     */
    public function tabloOlustur(Request $request)
    {
        $request->validate([
            'tablo_adi' => 'required|string|max:64',
            'kolonlar' => 'required|array|min:1',
            'kolonlar.*.ad' => 'required|string',
            'kolonlar.*.tip' => 'required|string',
        ]);

        try {
            $pdo = $this->baglan($request);
            $tablo = $request->tablo_adi;

            $kolonSqls = [];
            $primaryKey = null;

            foreach ($request->kolonlar as $k) {
                $sql = "`{$k['ad']}` {$k['tip']}";
                if (!empty($k['nullable']) && !$k['nullable']) $sql .= ' NOT NULL';
                if (isset($k['varsayilan']) && $k['varsayilan'] !== '') $sql .= " DEFAULT '{$k['varsayilan']}'";
                if (!empty($k['auto_increment'])) {
                    $sql .= ' AUTO_INCREMENT';
                    $primaryKey = $k['ad'];
                }
                $kolonSqls[] = $sql;
            }

            if ($primaryKey) {
                $kolonSqls[] = "PRIMARY KEY (`{$primaryKey}`)";
            }

            $createSql = "CREATE TABLE `{$tablo}` (\n  " . implode(",\n  ", $kolonSqls) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $pdo->exec($createSql);

            return response()->json(['hata' => false, 'mesaj' => "Tablo '{$tablo}' oluşturuldu.", 'sql' => $createSql]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Kolon ekle
     */
    public function kolonEkle(Request $request)
    {
        $request->validate([
            'tablo' => 'required|string',
            'kolon_adi' => 'required|string',
            'kolon_tipi' => 'required|string',
        ]);

        try {
            $pdo = $this->baglan($request);
            $sql = "ALTER TABLE `{$request->tablo}` ADD COLUMN `{$request->kolon_adi}` {$request->kolon_tipi}";
            if (!empty($request->nullable)) $sql .= ' NULL';
            else $sql .= ' NOT NULL';
            if ($request->varsayilan !== null && $request->varsayilan !== '') $sql .= " DEFAULT '{$request->varsayilan}'";
            if ($request->sonra) $sql .= " AFTER `{$request->sonra}`";

            $pdo->exec($sql);
            return response()->json(['hata' => false, 'mesaj' => "Kolon '{$request->kolon_adi}' eklendi."]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Kolon sil — Şifre doğrulama gerekli
     */
    public function kolonSil(Request $request)
    {
        $request->validate(['tablo' => 'required', 'kolon' => 'required']);

        $sifreHata = $this->sifreDogrula($request);
        if ($sifreHata) return $sifreHata;

        try {
            $pdo = $this->baglan($request);
            $pdo->exec("ALTER TABLE `{$request->tablo}` DROP COLUMN `{$request->kolon}`");
            return response()->json(['hata' => false, 'mesaj' => "Kolon '{$request->kolon}' silindi."]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Tabloyu boşalt — Şifre doğrulama gerekli
     */
    public function tabloTemizle(Request $request)
    {
        $request->validate(['tablo' => 'required']);

        $sifreHata = $this->sifreDogrula($request);
        if ($sifreHata) return $sifreHata;

        try {
            $pdo = $this->baglan($request);
            $pdo->exec("DELETE FROM `{$request->tablo}`");
            $pdo->exec("ALTER TABLE `{$request->tablo}` AUTO_INCREMENT = 1");
            return response()->json(['hata' => false, 'mesaj' => "Tablo '{$request->tablo}' temizlendi."]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Bağlantı testi
     */
    public function baglantiTest(Request $request)
    {
        try {
            $pdo = $this->baglan($request);
            $stmt = $pdo->query("SELECT VERSION() as v");
            $versiyon = $stmt->fetch(\PDO::FETCH_ASSOC)['v'];

            return response()->json(['hata' => false, 'mesaj' => "Bağlantı başarılı! MySQL {$versiyon}"]);
        } catch (\Exception $e) {
            return response()->json(['hata' => true, 'mesaj' => $e->getMessage()], 500);
        }
    }

    /**
     * Super Admin şifre doğrulama — Tehlikeli işlemler için zorunlu
     */
    private function sifreDogrula(Request $request)
    {
        $adminSifre = $request->input('admin_sifre');
        if (!$adminSifre) {
            return response()->json(['hata' => true, 'mesaj' => 'Bu işlem için Super Admin şifresi gereklidir.', 'sifre_gerekli' => true], 403);
        }

        $user = auth()->user();
        if (!Hash::check($adminSifre, $user->sifre)) {
            return response()->json(['hata' => true, 'mesaj' => 'Şifre hatalı! İşlem iptal edildi.', 'sifre_hatali' => true], 403);
        }

        return null; // Şifre doğru
    }

    /**
     * PDO bağlantısı oluştur (local veya remote)
     */
    private function baglan(Request $request): \PDO
    {
        $host = $request->db_host ?: config('database.connections.mysql.host');
        $port = $request->db_port ?: config('database.connections.mysql.port', 3306);
        $database = $request->database ?: config('database.connections.mysql.database');
        $username = $request->db_user ?: config('database.connections.mysql.username');
        $password = $request->db_pass ?? config('database.connections.mysql.password', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ]);
    }
}
