{{--
    Isi sel kolom Target/Capaian tahfizh di rapor cetak.
    $target: baris HafalanTarget yang sedang dirender.
    $mode: 'target' atau 'capaian'.
    Format 2/3 baris khusus dipakai pada target yang dibuat lewat alur UMMI
    (target->ummi_jilid terisi) -- target Reguler murni tetap memakai
    format satu baris "QS. Surah (Ayat X)" seperti sebelumnya.
    - Target: "Ummi : ..." + "Tahfizh : ..." (dari target itu sendiri).
    - Capaian: "Ummi : ..." + "Tahfizh Ummi : ..." (hafalan di dalam sesi
      UMMI) + "Tahfizh Mandiri : ..." (setoran hafalan terpisah) --
      ditampilkan berdampingan karena murid Kelas 10 punya dua jalur
      hafalan yang berbeda.
--}}
@php
    $isUmmiTarget = ! empty($target->ummi_jilid);

    // Halaman disimpan bebas oleh guru (kadang berupa rentang "24-25") --
    // rapor cukup menampilkan angka halaman terakhirnya saja.
    $lastPageNumber = function (?string $value) {
        if (! $value) {
            return $value;
        }
        $parts = preg_split('/[-–—]/', $value);

        return trim(end($parts));
    };
@endphp
@if ($isUmmiTarget)
    @if ($mode === 'target')
        {{-- ummi_jilid sudah berupa label lengkap ("Jilid 2", "Gharib", dst), tidak perlu prefix "Jilid" lagi. --}}
        @php $targetHalaman = $lastPageNumber($target->halaman_buku ?: $target->halaman_peraga); @endphp
        <div>Ummi : {{ $target->ummi_jilid }}{{ $targetHalaman ? ' Hal '.$targetHalaman : '' }}</div>
        @if ($target->surah)
            <div>Tahfizh : Surah {{ $target->surah->name_latin }}{{ $target->ayah ? ' Ayat '.$target->ayah : '' }}</div>
        @endif
    @else
        @php $capaianHalaman = $lastPageNumber($latestUmmiHalaman); @endphp
        <div>Ummi : {{ $latestUmmiJilid ?: '-' }}{{ $capaianHalaman ? ' Hal '.$capaianHalaman : '' }}</div>

        {{-- Tahfizh Ummi: hafalan yang dicatat di dalam sesi UMMI itu sendiri. --}}
        @php $ummiSurahAyah = $latestUmmiSurahEntry?->hafalan_ayah ? $lastPageNumber($latestUmmiSurahEntry->hafalan_ayah) : null; @endphp
        @if ($latestUmmiSurahEntry && $latestUmmiSurahEntry->surah)
            <div>Tahfizh Ummi : Surah {{ $latestUmmiSurahEntry->surah->name_latin }}{{ $ummiSurahAyah ? ' Ayat '.$ummiSurahAyah : '' }}</div>
        @endif

        {{-- Tahfizh Mandiri: setoran hafalan terpisah/mandiri (hafalan_records), di luar sesi UMMI. --}}
        @if (($latestJuz30Hafalan ?? null) && $latestJuz30Hafalan->surah)
            <div>Tahfizh Mandiri : Surah {{ $latestJuz30Hafalan->surah->name_latin }} Ayat {{ $latestJuz30Hafalan->ayah_end }}</div>
        @endif
    @endif
@elseif ($mode === 'target')
    QS. {{ $target->surah?->name_latin ?? '-' }} (Ayat {{ $target->ayah_range }})
@else
    @if ($target->matching_record)
        QS. {{ $target->matching_record->surah?->name_latin ?? '-' }} (Ayat {{ $target->matching_record->ayah_start }}-{{ $target->matching_record->ayah_end }})
    @else
        {{ $latestCapaianText ?: '-' }}
    @endif
@endif
