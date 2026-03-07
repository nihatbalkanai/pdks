<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DeploymentController extends Controller
{
    /**
     * Deployment sayfası — git diff ile değişen dosyaları göster
     */
    public function index()
    {
        // Sunucu ayarlarını .env'den veya config'den al
        $ayarlar = [
            'ftp_host' => config('deployment.ftp_host', ''),
            'ftp_user' => config('deployment.ftp_user', ''),
            'ftp_port' => config('deployment.ftp_port', 21),
            'ftp_root' => config('deployment.ftp_root', '/public_html'),
            'protokol' => config('deployment.protokol', 'ftp'), // ftp veya sftp
        ];

        return Inertia::render('SuperAdmin/Deployment', [
            'ayarlar' => $ayarlar,
        ]);
    }

    /**
     * Değişen dosyaları git diff ile getir
     */
    public function degisenDosyalar()
    {
        $basePath = base_path();

        // Son commit'ten bu yana değişen dosyalar
        $committed = trim(shell_exec("cd \"{$basePath}\" && git diff --name-status HEAD~1 HEAD 2>&1") ?? '');
        // Henüz commit edilmemiş değişiklikler
        $uncommitted = trim(shell_exec("cd \"{$basePath}\" && git diff --name-status 2>&1") ?? '');
        // Untracked (yeni) dosyalar
        $untracked = trim(shell_exec("cd \"{$basePath}\" && git ls-files --others --exclude-standard 2>&1") ?? '');

        $dosyalar = [];

        // Committed changes
        foreach (array_filter(explode("\n", $committed)) as $satir) {
            $parcalar = preg_split('/\s+/', trim($satir), 2);
            if (count($parcalar) === 2) {
                $durum = match ($parcalar[0]) { 'M' => 'degistirildi', 'A' => 'eklendi', 'D' => 'silindi', default => 'diger' };
                $dosyalar[$parcalar[1]] = ['dosya' => $parcalar[1], 'durum' => $durum, 'kaynak' => 'committed'];
            }
        }

        // Uncommitted changes
        foreach (array_filter(explode("\n", $uncommitted)) as $satir) {
            $parcalar = preg_split('/\s+/', trim($satir), 2);
            if (count($parcalar) === 2) {
                $durum = match ($parcalar[0]) { 'M' => 'degistirildi', 'A' => 'eklendi', 'D' => 'silindi', default => 'diger' };
                $dosyalar[$parcalar[1]] = ['dosya' => $parcalar[1], 'durum' => $durum, 'kaynak' => 'uncommitted'];
            }
        }

        // Untracked (yeni) dosyalar
        foreach (array_filter(explode("\n", $untracked)) as $dosya) {
            $dosya = trim($dosya);
            if ($dosya && !str_starts_with($dosya, 'mobile-app/node_modules') && !str_starts_with($dosya, 'node_modules')) {
                $dosyalar[$dosya] = ['dosya' => $dosya, 'durum' => 'yeni', 'kaynak' => 'untracked'];
            }
        }

        // Her dosyanın boyutunu hesapla
        foreach ($dosyalar as &$d) {
            $fullPath = base_path($d['dosya']);
            $d['boyut'] = file_exists($fullPath) ? filesize($fullPath) : 0;
            $d['boyut_str'] = $this->formatBoyut($d['boyut']);
        }

        return response()->json([
            'dosyalar' => array_values($dosyalar),
            'toplam' => count($dosyalar),
        ]);
    }

    /**
     * Seçilen dosyaları sunucuya FTP ile yükle
     */
    public function yukle(Request $request)
    {
        $request->validate([
            'dosyalar' => 'required|array|min:1',
            'dosyalar.*' => 'string',
            'host' => 'required|string',
            'user' => 'required|string',
            'pass' => 'required|string',
            'port' => 'integer',
            'root' => 'required|string',
            'protokol' => 'in:ftp,sftp',
        ]);

        $sonuclar = [];
        $basarili = 0;
        $basarisiz = 0;

        if ($request->protokol === 'sftp') {
            // SFTP Bağlantısı
            $connection = @ssh2_connect($request->host, $request->port ?: 22);
            if (!$connection) {
                return response()->json(['hata' => true, 'mesaj' => 'SFTP bağlantısı kurulamadı.'], 500);
            }
            if (!@ssh2_auth_password($connection, $request->user, $request->pass)) {
                return response()->json(['hata' => true, 'mesaj' => 'SFTP kimlik doğrulama başarısız.'], 401);
            }
            $sftp = ssh2_sftp($connection);

            foreach ($request->dosyalar as $dosya) {
                $localPath = base_path($dosya);
                $remotePath = rtrim($request->root, '/') . '/' . $dosya;

                if (!file_exists($localPath)) {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'hata', 'mesaj' => 'Lokal dosya bulunamadı'];
                    $basarisiz++;
                    continue;
                }

                // Uzak dizin oluştur
                $remoteDir = dirname($remotePath);
                @ssh2_sftp_mkdir($sftp, $remoteDir, 0755, true);

                $icerik = file_get_contents($localPath);
                $result = @file_put_contents("ssh2.sftp://{$sftp}{$remotePath}", $icerik);

                if ($result !== false) {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'basarili'];
                    $basarili++;
                } else {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'hata', 'mesaj' => 'Yazma hatası'];
                    $basarisiz++;
                }
            }
        } else {
            // FTP Bağlantısı
            $ftp = @ftp_connect($request->host, $request->port ?: 21, 30);
            if (!$ftp) {
                return response()->json(['hata' => true, 'mesaj' => 'FTP bağlantısı kurulamadı. Host/port kontrol edin.'], 500);
            }

            if (!@ftp_login($ftp, $request->user, $request->pass)) {
                ftp_close($ftp);
                return response()->json(['hata' => true, 'mesaj' => 'FTP giriş bilgileri hatalı.'], 401);
            }

            ftp_pasv($ftp, true); // Passive mod

            foreach ($request->dosyalar as $dosya) {
                $localPath = base_path($dosya);
                $remotePath = rtrim($request->root, '/') . '/' . $dosya;

                if (!file_exists($localPath)) {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'hata', 'mesaj' => 'Lokal dosya bulunamadı'];
                    $basarisiz++;
                    continue;
                }

                // Uzak dizin oluştur (recursive)
                $remoteDir = dirname($remotePath);
                $this->ftpMkdirRecursive($ftp, $remoteDir);

                if (@ftp_put($ftp, $remotePath, $localPath, FTP_BINARY)) {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'basarili'];
                    $basarili++;
                } else {
                    $sonuclar[] = ['dosya' => $dosya, 'durum' => 'hata', 'mesaj' => 'Yükleme başarısız'];
                    $basarisiz++;
                }
            }

            ftp_close($ftp);
        }

        return response()->json([
            'hata' => false,
            'mesaj' => "{$basarili} dosya yüklendi" . ($basarisiz > 0 ? ", {$basarisiz} başarısız" : ""),
            'basarili' => $basarili,
            'basarisiz' => $basarisiz,
            'sonuclar' => $sonuclar,
        ]);
    }

    /**
     * FTP bağlantı testi
     */
    public function baglantiTest(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'user' => 'required|string',
            'pass' => 'required|string',
            'port' => 'integer',
        ]);

        $ftp = @ftp_connect($request->host, $request->port ?: 21, 10);
        if (!$ftp) {
            return response()->json(['hata' => true, 'mesaj' => 'Bağlantı kurulamadı.']);
        }

        if (!@ftp_login($ftp, $request->user, $request->pass)) {
            ftp_close($ftp);
            return response()->json(['hata' => true, 'mesaj' => 'Giriş bilgileri hatalı.']);
        }

        ftp_pasv($ftp, true);
        $pwd = ftp_pwd($ftp);
        ftp_close($ftp);

        return response()->json(['hata' => false, 'mesaj' => "Bağlantı başarılı! Dizin: {$pwd}"]);
    }

    /**
     * FTP recursive mkdir
     */
    private function ftpMkdirRecursive($ftp, $dir): void
    {
        $parts = explode('/', $dir);
        $path = '';
        foreach ($parts as $part) {
            $path .= '/' . $part;
            @ftp_mkdir($ftp, $path);
        }
    }

    private function formatBoyut($bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
