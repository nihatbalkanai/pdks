<?php

namespace App\Exports;

use App\Models\PdksGunlukOzet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class RaporExport implements FromQuery, WithMapping, WithHeadings, WithChunkReading
{
    use Exportable;

    protected $sorgu;

    public function __construct($sorgu)
    {
        // Eloquent Builder Query'si bekliyoruz (Query Builder ile de kullanılabilir)
        $this->sorgu = $sorgu;
    }

    public function query()
    {
        return $this->sorgu;
    }

    public function map($satir): array
    {
        return [
            $satir->personel ? $satir->personel->ad_soyad : 'Bilinmiyor',
            $satir->personel ? $satir->personel->sicil_no : '-',
            $satir->personel ? $satir->personel->bolum : '-',
            $satir->tarih,
            $satir->ilk_giris,
            $satir->son_cikis,
            $satir->toplam_calisma_suresi,
            $satir->durum
        ];
    }

    public function headings(): array
    {
        return [
            'Ad Soyad',
            'Sicil No',
            'Bölüm',
            'Tarih',
            'İlk Giriş',
            'Son Çıkış',
            'Toplam Çalışma (Dk)',
            'Durum'
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
