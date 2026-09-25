<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Audit Log
            </h2>
            <p class="text-sm text-gray-600 mt-1">
                Riwayat aktivitas penting pada sistem TAD.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-sm text-gray-500">Total Log</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['total'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-sm text-gray-500">Log Hari Ini</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ $summary['today'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-sm text-gray-500">Update Hari Ini</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-700">
                        {{ $summary['updates_today'] ?? 0 }}
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <p class="text-sm text-gray-500">Delete Hari Ini</p>
                    <p class="mt-2 text-3xl font-bold text-red-700">
                        {{ $summary['deletes_today'] ?? 0 }}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 sm:p-5">
                <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 sm:gap-4">
                    <div class="sm:col-span-2 lg:col-span-2">
                        <label for="search" class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            Pencarian
                        </label>
                        <input id="search"
                               type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="User, objek, IP, URL..."
                               x-on:input.debounce.600ms="$el.form.submit()"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="event" class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            Event
                        </label>
                        <select id="event"
                                name="event"
                                onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Semua</option>

                            @foreach ($events as $event)
                                <option value="{{ $event }}" @selected(request('event') === $event)>
                                    {{ match ($event) {
                                        'created' => 'Dibuat',
                                        'updated' => 'Diubah',
                                        'deleted' => 'Dihapus',
                                        'restored' => 'Dipulihkan',
                                        'force_deleted' => 'Dihapus Permanen',
                                        default => ucfirst($event),
                                    } }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="auditable_label" class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            Objek
                        </label>
                        <select id="auditable_label"
                                name="auditable_label"
                                onchange="this.form.submit()"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="">Semua</option>

                            @foreach ($auditableLabels as $label)
                                <option value="{{ $label }}" @selected(request('auditable_label') === $label)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            Dari
                        </label>
                        <input id="date_from"
                               type="date"
                               name="date_from"
                               value="{{ request('date_from') }}"
                               onchange="this.form.submit()"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="date_to" class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            Sampai
                        </label>
                        <input id="date_to"
                               type="date"
                               name="date_to"
                               value="{{ request('date_to') }}"
                               onchange="this.form.submit()"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap gap-2">
                        <button type="submit"
                                style="background-color: #111827; color: #ffffff;"
                                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90 active:scale-95 transition">
                            Filter
                        </button>

                        <a href="{{ route('audit-logs.index') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 active:scale-95 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto touch-scroll">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Waktu
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Event
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Objek
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    IP
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Detail
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($auditLogs as $auditLog)
                                <tr>
                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $auditLog->created_at?->format('d M Y H:i') }}
                                    </td>

                                    <td class="px-5 py-4 text-sm">
                                        <div class="font-semibold text-gray-900">
                                            {{ $auditLog->user?->name ?? 'System' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $auditLog->user?->email ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                            @if ($auditLog->event === 'created') bg-green-50 text-green-700
                                            @elseif ($auditLog->event === 'updated') bg-indigo-50 text-indigo-700
                                            @elseif (in_array($auditLog->event, ['deleted', 'force_deleted'], true)) bg-red-50 text-red-700
                                            @else bg-gray-100 text-gray-700
                                            @endif">
                                            {{ $auditLog->event_label }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-sm">
                                        <div class="font-semibold text-gray-900">
                                            {{ $auditLog->auditable_type_label }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $auditLog->auditable_label ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $auditLog->ip_address ?? '-' }}
                                    </td>

                                    <td class="px-5 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('audit-logs.show', $auditLog) }}"
                                           class="btn-action-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">
                                        Belum ada audit log.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($auditLogs->hasPages())
                    <div class="px-5 py-4 border-t border-gray-200">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>