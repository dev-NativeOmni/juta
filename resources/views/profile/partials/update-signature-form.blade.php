{{-- Tanda tangan guru pengampu: dipakai di Laporan Triwulan (Jurnal & Capaian Hafalan). --}}
<section>
    <header>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
            {{ __('Tanda Tangan') }}
        </h2>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Tanda tangan ini dicantumkan di bawah Jurnal dan Capaian Hafalan pada Laporan Triwulan untuk kelas/halaqoh yang Anda ampu.
            Gunakan PNG berlatar transparan (tinta hitam/biru), maksimal 1MB.
        </p>
    </header>

    @if (session('status') === 'signature-updated')
        <div class="mt-4 p-3 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-sm font-bold">
            Tanda tangan berhasil disimpan.
        </div>
    @elseif (session('status') === 'signature-deleted')
        <div class="mt-4 p-3 bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-xl text-sm font-bold">
            Tanda tangan dihapus.
        </div>
    @endif

    <div class="mt-5 h-28 rounded-xl bg-white border border-dashed border-zinc-300 dark:border-zinc-700 flex items-center justify-center overflow-hidden">
        @if ($signaturePreview)
            <img src="{{ $signaturePreview }}" alt="Tanda tangan {{ $user->name }}" class="max-h-full max-w-full object-contain">
        @else
            <span class="text-sm text-zinc-400">Belum ada tanda tangan</span>
        @endif
    </div>

    <form method="post" action="{{ route('profile.signature.update') }}" enctype="multipart/form-data" class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
        @csrf
        <input type="file" name="signature" accept="image/png,image/jpeg,image/webp" required
               class="block w-full text-sm text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
        <x-primary-button class="shrink-0">{{ __('Simpan') }}</x-primary-button>
    </form>
    <x-input-error :messages="$errors->get('signature')" class="mt-2" />

    @if ($signaturePreview)
        <form method="post" action="{{ route('profile.signature.destroy') }}" class="mt-3" onsubmit="return confirm('Hapus tanda tangan Anda?')">
            @csrf
            @method('delete')
            <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 cursor-pointer">Hapus tanda tangan</button>
        </form>
    @endif
</section>
