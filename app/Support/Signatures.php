<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Tanda tangan untuk dokumen cetak (rapor & Laporan Triwulan).
 *
 * Berkas disimpan di disk privat 'local' (tidak bisa diakses lewat URL publik):
 * di halaman web disematkan sebagai data URI, di Excel ditanam sebagai gambar.
 * Tanda tangan pejabat diatur Super Admin di Pengaturan Umum; nama/NIK/jabatannya
 * tetap dari Pengaturan Rapor. Tanda tangan guru diunggah sendiri di Profil.
 */
class Signatures
{
    private const DISK = 'local';

    /**
     * Pejabat penanda tangan: kunci => [label, setting berkas, setting nama, setting NIK, default nama].
     */
    public const OFFICIALS = [
        'headmaster' => ['label' => 'Kepala Sekolah', 'file' => 'signature_headmaster', 'name' => 'report_headmaster_name', 'nik' => 'report_headmaster_nik', 'default' => 'Moh Pandoyo, S.Si., M.Pd., Gr.'],
        'coord_tahfizh' => ['label' => 'Koordinator Tahfizh', 'file' => 'signature_coord_tahfizh', 'name' => 'report_coord_tahfizh_name', 'nik' => 'report_coord_tahfizh_nik', 'default' => 'Zainal Arifin, S.Pd'],
        'coord_keagamaan' => ['label' => 'Koordinator Keagamaan (Adab)', 'file' => 'signature_coord_keagamaan', 'name' => 'report_coord_keagamaan_name', 'nik' => 'report_coord_keagamaan_nik', 'default' => 'Rifqi Ihsan, S.Pd., Gr.'],
        'coord_tanse' => ['label' => 'Koordinator Tanse', 'file' => 'signature_coord_tanse', 'name' => 'report_coord_tanse_name', 'nik' => 'report_coord_tanse_nik', 'default' => 'Yatim Hermawan, S.E., S.Kom'],
    ];

    public const UPLOAD_RULES = ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'];

    /**
     * Berkas tanda tangan pejabat (path relatif di disk) atau null.
     */
    public static function officialFile(string $key): ?string
    {
        return Setting::get(self::OFFICIALS[$key]['file']) ?: null;
    }

    /**
     * Nama & NIK pejabat dari Pengaturan Rapor.
     *
     * @return array{name: string, nik: string}
     */
    public static function officialIdentity(string $key): array
    {
        $official = self::OFFICIALS[$key];

        return [
            'name' => (string) Setting::get($official['name'], $official['default']),
            'nik' => (string) Setting::get($official['nik'], ''),
        ];
    }

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store('signatures/'.$folder, self::DISK);
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * Path absolut berkas untuk Excel, atau null bila tidak ada.
     */
    public static function absolutePath(?string $path): ?string
    {
        return $path && Storage::disk(self::DISK)->exists($path)
            ? Storage::disk(self::DISK)->path($path)
            : null;
    }

    /**
     * Data URI untuk <img> di halaman web/cetak, atau null bila tidak ada.
     */
    public static function dataUri(?string $path): ?string
    {
        $absolute = self::absolutePath($path);
        if ($absolute === null || ! is_file($absolute)) {
            return null;
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        $content = @file_get_contents($absolute);
        if ($content === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}
