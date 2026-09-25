<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">
                Backup Database
            </h2>
            <p class="text-sm text-gray-600">
                Kelola backup database TAD secara manual dari panel admin.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Total Backup</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ count($backups) }}
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Retention</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $retentionDays }} Hari
                    </p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Backup Terbaru</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900">
                        {{ $latestBackup['created_at'] ?? 'Belum ada backup' }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Buat Backup Baru
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Backup akan disimpan sebagai file SQL lokal di server.
                        </p>
                        <p class="mt-2 break-all text-xs text-gray-500">
                            Path: {{ $backupPath }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('database-backups.store') }}">
                        @csrf

                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
                            Buat Backup Sekarang
                        </button>
                    </form>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Daftar Backup
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Download atau hapus file backup yang tidak dibutuhkan.
                    </p>
                </div>

                @if (count($backups) === 0)
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-medium text-gray-700">
                            Belum ada backup database.
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Klik tombol “Buat Backup Sekarang” untuk membuat backup pertama.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                        File
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                        Ukuran
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                        Dibuat
                                    </th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($backups as $backup)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <div class="max-w-xl truncate text-sm font-semibold text-gray-900">
                                                {{ $backup['filename'] }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $backup['size'] }}
                                        </td>
                                        <td class="px-5 py-4 text-sm text-gray-700">
                                            {{ $backup['created_at'] }}
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('database-backups.download', $backup['filename']) }}"
                                                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                                    Download
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('database-backups.destroy', $backup['filename']) }}"
                                                      onsubmit="return confirm('Hapus file backup ini? Tindakan ini tidak bisa dibatalkan.')">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                            class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5">
                <h3 class="text-sm font-semibold text-yellow-900">
                    Catatan Keamanan
                </h3>
                <p class="mt-1 text-sm text-yellow-800">
                    File backup berisi seluruh data database. Jangan upload file backup ke GitHub,
                    jangan letakkan di folder public, dan jangan bagikan ke pihak yang tidak berwenang.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>