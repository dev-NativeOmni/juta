<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Setoran Hafalan
            </h2>

            <a
                href="{{ route('hafalan-records.edit', $hafalanRecord) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Edit Setoran
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Informasi Setoran
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tanggal</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->submitted_at?->format('d M Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Murid</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->student?->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Kelas</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->student?->classRoom?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->teacher?->user?->name ?: '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm text-gray-500 mb-2">Daftar Setoran</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                    <th class="px-3 py-2">Surah</th>
                                    <th class="px-3 py-2">Ayat</th>
                                    <th class="px-3 py-2">Jenis</th>
                                    <th class="px-3 py-2">Nilai</th>
                                    <th class="px-3 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($hafalanRecord->surahs as $surahEntry)
                                    <tr>
                                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $surahEntry->surah?->number }}. {{ $surahEntry->surah?->name_latin }}</td>
                                        <td class="px-3 py-2">{{ $surahEntry->ayah_start }} - {{ $surahEntry->ayah_end }}</td>
                                        <td class="px-3 py-2">{{ $surahEntry->submission_type_label }}</td>
                                        <td class="px-3 py-2">{{ $surahEntry->score !== null ? number_format((float) $surahEntry->score, 2) : '-' }}</td>
                                        <td class="px-3 py-2">{{ $surahEntry->status_label }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-4 text-center text-gray-400">Belum ada surah tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-sm text-gray-500">Catatan Guru</p>
                    <p class="text-gray-800 whitespace-pre-line">
                        {{ $hafalanRecord->notes ?: '-' }}
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('hafalan-records.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar setoran
                </a>
            </div>
        </div>
    </div>
</x-app-layout>