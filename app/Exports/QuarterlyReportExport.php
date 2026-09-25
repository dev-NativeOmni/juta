<?php

namespace App\Exports;

use App\Exports\QuarterlyReport\GrafikAkhirBulanSheet;
use App\Exports\QuarterlyReport\JurnalSheet;
use App\Exports\QuarterlyReport\PresensiSheet;
use App\Exports\QuarterlyReport\SetoranSheet;
use App\Exports\QuarterlyReport\TermIndexSheet;
use App\Models\Setting;
use App\Support\Signatures;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor Laporan Triwulan ke satu file .xlsx, satu sheet per tab yang tampil di
 * layar (Term/Indeks, Presensi, Jurnal, Setoran, Grafik Akhir Bulan) -- dibangun
 * dari data yang sama persis dengan yang dipakai untuk merender halaman (lihat
 * QuarterlyReportController::buildReportData()), supaya isinya selalu sinkron.
 */
class QuarterlyReportExport implements WithMultipleSheets
{
    public function __construct(private readonly array $data) {}

    /**
     * Data blok tanda tangan (lihat Concerns\SignatureBlock): kepala sekolah dari
     * Pengaturan Rapor + Pengaturan Umum, guru pengampu dari Profil masing-masing.
     */
    private function signatureContext(): array
    {
        $headmaster = Signatures::officialIdentity('headmaster');

        return [
            'city' => (string) Setting::get('report_city', 'Sukoharjo'),
            // Titimangsa: tanggal terakhir triwulan yang dilaporkan.
            'date' => ! empty($this->data['termEndDate'])
                ? Carbon::parse($this->data['termEndDate'])->locale('id')->translatedFormat('j F Y')
                : '',
            'headmaster_title' => (string) Setting::get('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo'),
            'headmaster_name' => $headmaster['name'],
            'headmaster_nik' => $headmaster['nik'],
            'headmaster_signature' => Signatures::absolutePath(Signatures::officialFile('headmaster')),
            'teacher_signatures' => collect($this->data['halaqahData'])
                ->pluck('musyrif_signature')
                ->filter()
                ->unique()
                ->mapWithKeys(fn ($path) => [$path => Signatures::absolutePath($path)])
                ->all(),
        ];
    }

    public function sheets(): array
    {
        return [
            new TermIndexSheet($this->data['halaqahData']),
            new PresensiSheet($this->data['halaqahData'], $this->data['isTahfizhProgram']),
            new JurnalSheet($this->data['halaqahData'], $this->signatureContext()),
            new SetoranSheet($this->data['halaqahData'], $this->data['isTahfizhProgram'], $this->signatureContext()),
            new GrafikAkhirBulanSheet($this->data['halaqahData']),
        ];
    }
}
