<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Murid
            </h2>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('hafalan-targets.juz-orders', $student) }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                >
                    Urutan Hafalan
                </a>
                <a
                    href="{{ route('students.edit', $student) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Edit Murid
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Identitas Murid
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama</p>
                        <p class="font-semibold text-gray-900">{{ $student->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Nomor Murid / NIS</p>
                        <p class="font-semibold text-gray-900">{{ $student->student_number ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Gender</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Tanggal Lahir</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->birth_date?->format('d M Y') ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-gray-900">
                            @if ($student->status === 'active')
                                Aktif
                            @elseif ($student->status === 'inactive')
                                Nonaktif
                            @else
                                Lulus
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Username Login</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->user?->username ?: 'Belum dihubungkan' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Akademik
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Program</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->classRoom?->program?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Kelas</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->classRoom?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="font-semibold text-gray-900">
                            {{ $student->teacher?->user?->name ?: '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Orangtua/Wali
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Username</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Relasi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->parents as $parent)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $parent->user?->name }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->user?->username ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->phone ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->pivot?->relation ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada orangtua/wali terhubung.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kedisiplinan: Poin Pelanggaran & Penghargaan -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-150 pb-4 mb-4">
                    <h3 class="font-semibold text-gray-900">
                        Kedisiplinan (Poin & Disiplin)
                    </h3>
                    @php
                        $violations = $student->points->where('type', 'violation');
                        $rewards = $student->points->where('type', 'reward');
                        $totalViolationsVal = $violations->sum('points');
                        $totalRewardsVal = $rewards->sum('points');
                        $netBalance = $totalRewardsVal - $totalViolationsVal;
                    @endphp
                    <div class="flex flex-wrap gap-2 mt-2 sm:mt-0 text-xs">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full font-bold bg-red-50 text-red-700 border border-red-200">
                            Total Pelanggaran: {{ $totalViolationsVal }} Poin
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full font-bold bg-green-50 text-green-700 border border-green-200">
                            Total Penghargaan: {{ $totalRewardsVal }} Poin
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full font-bold {{ $netBalance >= 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-amber-50 text-amber-700 border-amber-200' }} border">
                            Selisih: {{ $netBalance > 0 ? '+' : '' }}{{ $netBalance }} Poin
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Nama Pelanggaran / Penghargaan</th>
                                <th class="px-4 py-3 text-center">Tipe</th>
                                <th class="px-4 py-3 text-center">Poin</th>
                                <th class="px-4 py-3">Dicatat Oleh</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($student->points->sortByDesc('date') as $point)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $point->date?->format('d/m/Y') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="font-semibold">{{ $point->title }}</div>
                                        @if ($point->description)
                                            <div class="text-xs text-gray-500 mt-0.5">{{ $point->description }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @if ($point->type === 'violation')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 uppercase">
                                                Pelanggaran
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-200 uppercase">
                                                Penghargaan
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center text-sm font-bold">
                                        <span class="{{ $point->type === 'violation' ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $point->type === 'violation' ? '-' : '+' }}{{ $point->points }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $point->logger?->name ?? 'Sistem' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 text-sm">
                                        Belum ada catatan poin pelanggaran atau penghargaan untuk murid ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar murid
                </a>
            </div>
        </div>
    </div>
</x-app-layout>