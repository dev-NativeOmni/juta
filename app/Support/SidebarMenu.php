<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Satu sumber data untuk menu sidebar web.
 *
 * Setiap item menyebut role yang boleh melihatnya lewat 'roles' (daftar izin)
 * atau 'except' (semua role kecuali daftar ini). Pengecekan memakai
 * User::hasAnyRole(), yang hanya melihat role AKTIF (role switcher), jadi
 * daftar di sini harus sejalan dengan middleware di routes/web.php --
 * menu yang tampil tapi 403 saat diklik adalah bug.
 */
class SidebarMenu
{
    private const ALL_STAFF = ['super_admin', 'admin', 'teacher', 'supervisor', 'coordinator_tahfizh'];

    private const ICONS = [
        'home' => ['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        'bell' => ['M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
        'academic-cap' => ['M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222'],
        'building' => ['M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        'calendar' => ['M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
        'clock' => ['M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'briefcase' => ['M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        'heart' => ['M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        'user-group' => ['M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
        'users' => ['M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        'pencil' => ['M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        'book' => ['M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
        'document' => ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'document-chart' => ['M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        'badge-check' => ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
        'trending-up' => ['M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
        'chart-pie' => ['M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z', 'M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z'],
        'chat' => ['M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        'user-circle-group' => ['M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4 4 4 0 004 4z'],
        'shield-check' => ['M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
        'cog' => [
            'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        ],
        'bookmark' => ['M5 3v16l7-5 7 5V3H5z'],
        'clipboard' => ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        // Ikon header grup
        'squares' => ['M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
    ];

    /**
     * Daftar grup menu. Urutan di sini = urutan tampil.
     *
     * Item: label, route, icon, roles|except, active (pola routeIs),
     * active_except (pola yang membatalkan status aktif).
     */
    private static function definition(): array
    {
        return [
            [
                'key' => 'utama',
                'label' => 'Utama',
                'collapsible' => false,
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'active' => ['dashboard', '*.dashboard']],
                    ['label' => 'Notifikasi', 'route' => 'system-notifications.index', 'icon' => 'bell', 'active' => ['system-notifications.*'], 'badge' => 'unread_notifications'],
                ],
            ],
            [
                'key' => 'tahfizh',
                'label' => 'Tahfizh',
                'icon' => 'book',
                'items' => [
                    [
                        'label' => "Input Setoran & Muraja'ah", 'route' => 'spreadsheet-input.index', 'icon' => 'pencil',
                        'roles' => self::ALL_STAFF,
                        'active' => ['spreadsheet-input.*', 'hafalan-records.create', 'murajaah-records.fast-input', 'murajaah-records.create'],
                    ],
                    [
                        'label' => 'Riwayat Setoran', 'route' => 'hafalan-records.index', 'icon' => 'book',
                        'roles' => self::ALL_STAFF,
                        'active' => ['hafalan-records.*', 'murajaah-records.*'],
                        'active_except' => ['hafalan-records.create', 'murajaah-records.fast-input', 'murajaah-records.create'],
                    ],
                    ['label' => 'Ujian Tahfizh', 'route' => 'tahfizh-exams.index', 'icon' => 'document', 'roles' => self::ALL_STAFF, 'active' => ['tahfizh-exams.*']],
                    [
                        'label' => 'Target Bulanan', 'route' => 'hafalan-targets.index', 'icon' => 'badge-check', 'roles' => self::ALL_STAFF,
                        'active' => ['hafalan-targets.*'], 'active_except' => ['hafalan-targets.term'],
                    ],
                    ['label' => 'Target Triwulan', 'route' => 'hafalan-targets.term', 'icon' => 'chart-pie', 'roles' => self::ALL_STAFF, 'active' => ['hafalan-targets.term']],
                    [
                        'label' => 'Progress', 'route' => 'progress.index', 'icon' => 'trending-up',
                        'roles' => [...self::ALL_STAFF, 'parent', 'student'],
                        'active' => ['progress.*'],
                    ],
                    [
                        'label' => "Mushaf Al-Qur'an", 'route' => 'quran.mushaf', 'icon' => 'book',
                        'roles' => [...self::ALL_STAFF, 'parent', 'student'],
                        'active' => ['quran.mushaf'],
                    ],
                ],
            ],
            [
                'key' => 'adab',
                'label' => 'Adab & Keagamaan',
                'icon' => 'shield-check',
                'items' => [
                    [
                        'label' => 'Adab', 'route' => 'adab.index', 'icon' => 'shield-check',
                        'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'pendamping_adab', 'parent', 'student'],
                        'active' => ['adab.index', 'adab.show'],
                    ],
                    // Koordinator Keagamaan/Adab: kalender untuk mengatur & mengunci libur Adab (Admin lewat Data Master).
                    ['label' => 'Kalender Adab', 'route' => 'academic-calendar.index', 'icon' => 'calendar', 'roles' => ['supervisor'], 'active' => ['academic-calendar.*']],
                    [
                        'label' => 'Materi Adab', 'route' => 'adab-materials.index', 'icon' => 'book',
                        'except' => ['parent', 'student', 'headmaster'],
                        'active' => ['adab-materials.*'],
                    ],
                ],
            ],
            [
                'key' => 'ketahanan',
                'label' => 'Ketahanan Sekolah',
                'icon' => 'clipboard',
                'items' => [
                    [
                        'label' => 'Poin & Disiplin', 'route' => 'student-points.index', 'icon' => 'shield-check',
                        'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'tanse', 'parent', 'student'],
                        'active' => ['student-points.*'],
                        'active_except' => ['student-points.chart'],
                    ],
                ],
            ],
            [
                'key' => 'laporan',
                'label' => 'Laporan & Analitik',
                'icon' => 'document-chart',
                'items' => [
                    [
                        'label' => 'Rapor Digital', 'route' => 'digital-reports.index', 'icon' => 'document',
                        'roles' => ['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'supervisor', 'tanse', 'pendamping_adab', 'headmaster', 'wali_kelas'],
                        'active' => ['digital-reports.index', 'digital-reports.show'],
                    ],
                    ['label' => 'Laporan Triwulan', 'route' => 'reports.quarterly', 'icon' => 'document-chart', 'roles' => ['super_admin', 'admin', 'teacher'], 'active' => ['reports.quarterly']],
                    // reports.whatsapp tidak mengizinkan tanse di middleware route-nya.
                    ['label' => 'Laporan WA Harian', 'route' => 'reports.whatsapp', 'icon' => 'chat', 'roles' => ['super_admin', 'admin', 'teacher', 'coordinator_tahfizh'], 'active' => ['reports.whatsapp']],
                    [
                        'label' => 'Grafik Tahfizh', 'route' => 'reports.periodic', 'icon' => 'chart-pie',
                        'roles' => ['super_admin', 'admin', 'teacher', 'headmaster', 'coordinator_tahfizh', 'supervisor'],
                        'active' => ['reports.periodic', 'reports.periodic.print'],
                    ],
                    [
                        'label' => 'Grafik Adab', 'route' => 'adab.chart', 'icon' => 'chart-pie',
                        'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'headmaster', 'pendamping_adab'],
                        'active' => ['adab.chart'],
                    ],
                    [
                        'label' => 'Grafik Poin & Disiplin', 'route' => 'student-points.chart', 'icon' => 'chart-pie',
                        'roles' => ['super_admin', 'admin', 'teacher', 'supervisor', 'headmaster', 'tanse'],
                        'active' => ['student-points.chart'],
                    ],
                    ['label' => 'Pemantauan Wali Kelas', 'route' => 'wali-kelas.index', 'icon' => 'user-circle-group', 'roles' => ['wali_kelas'], 'active' => ['wali-kelas.index']],
                    ['label' => 'Kinerja Guru', 'route' => 'reports.teachers', 'icon' => 'users', 'roles' => ['super_admin', 'admin'], 'active' => ['reports.teachers']],
                ],
            ],
            [
                'key' => 'data-master',
                'label' => 'Data Master',
                'icon' => 'squares',
                'items' => [
                    ['label' => 'Program', 'route' => 'programs.index', 'icon' => 'academic-cap', 'roles' => ['super_admin', 'admin'], 'active' => ['programs.*']],
                    ['label' => 'Kelas', 'route' => 'class-rooms.index', 'icon' => 'building', 'roles' => ['super_admin', 'admin'], 'active' => ['class-rooms.*']],
                    ['label' => 'Jadwal Kelas', 'route' => 'class-schedules.index', 'icon' => 'calendar', 'roles' => ['super_admin', 'admin'], 'active' => ['class-schedules.index']],
                    ['label' => 'Kalender Akademik', 'route' => 'academic-calendar.index', 'icon' => 'clock', 'roles' => ['super_admin', 'admin'], 'active' => ['academic-calendar.*']],
                    ['label' => 'Guru', 'route' => 'teachers.index', 'icon' => 'briefcase', 'roles' => ['super_admin', 'admin'], 'active' => ['teachers.*']],
                    ['label' => 'Orangtua', 'route' => 'parents.index', 'icon' => 'heart', 'roles' => ['super_admin', 'admin'], 'active' => ['parents.*']],
                    ['label' => 'Murid', 'route' => 'students.index', 'icon' => 'user-group', 'roles' => ['super_admin', 'admin'], 'active' => ['students.*']],
                ],
            ],
            [
                'key' => 'pengaturan',
                'label' => 'Pengaturan',
                'icon' => 'cog',
                'items' => [
                    [
                        'label' => 'Pengaturan Umum', 'route' => 'settings.index', 'icon' => 'cog',
                        'roles' => ['super_admin'],
                        'active' => ['settings.*'],
                        'active_except' => ['settings.tahfizh-scoring*', 'settings.adab'],
                    ],
                    ['label' => 'Penilaian Tahfizh', 'route' => 'settings.tahfizh-scoring', 'icon' => 'cog', 'roles' => ['super_admin', 'admin'], 'active' => ['settings.tahfizh-scoring*']],
                    ['label' => 'Pengaturan Adab', 'route' => 'settings.adab', 'icon' => 'cog', 'roles' => ['super_admin', 'admin', 'supervisor'], 'active' => ['settings.adab']],
                    // Pengaturan Rapor: khusus super_admin & admin (lihat catatan di routes/web.php).
                    ['label' => 'Pengaturan Rapor', 'route' => 'digital-reports.settings', 'icon' => 'cog', 'roles' => ['super_admin', 'admin'], 'active' => ['digital-reports.settings']],
                    ['label' => 'Badge', 'route' => 'badges.index', 'icon' => 'bookmark', 'roles' => ['super_admin'], 'active' => ['badges.*']],
                    ['label' => 'Manajemen User', 'route' => 'users.index', 'icon' => 'users', 'roles' => ['super_admin'], 'active' => ['users.*']],
                    ['label' => 'Audit Log', 'route' => 'audit-logs.index', 'icon' => 'document', 'roles' => ['super_admin', 'admin'], 'active' => ['audit-logs.*']],
                ],
            ],
        ];
    }

    /**
     * Grup menu yang terlihat oleh user (item tanpa izin/route dibuang,
     * grup kosong dibuang), lengkap dengan status aktif.
     *
     * @return array<int, array{key: string, label: string, icon: ?string, collapsible: bool, active: bool, items: array}>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $groups = [];

        foreach (self::definition() as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (! Route::has($item['route']) || ! self::canSee($user, $item)) {
                    continue;
                }

                $items[] = [
                    'label' => $item['label'],
                    'url' => route($item['route']),
                    'icon' => $item['icon'],
                    'active' => self::isActive($item),
                    'badge' => isset($item['badge']) ? self::badge($user, $item['badge']) : 0,
                ];
            }

            if ($items === []) {
                continue;
            }

            $groups[] = [
                'key' => $group['key'],
                'label' => $group['label'],
                'icon' => $group['icon'] ?? null,
                'collapsible' => ($group['collapsible'] ?? true) && count($items) > 1,
                'active' => collect($items)->contains('active', true),
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * Path SVG (stroke) untuk nama ikon.
     *
     * @return array<int, string>
     */
    public static function icon(string $name): array
    {
        return self::ICONS[$name] ?? [];
    }

    private static function canSee(User $user, array $item): bool
    {
        if (isset($item['roles'])) {
            return $user->hasAnyRole($item['roles']);
        }

        if (isset($item['except'])) {
            return ! $user->hasAnyRole($item['except']);
        }

        return true;
    }

    private static function isActive(array $item): bool
    {
        $request = request();

        return $request->routeIs(...$item['active'])
            && ! (isset($item['active_except']) && $request->routeIs(...$item['active_except']));
    }

    private static function badge(User $user, string $type): int
    {
        return match ($type) {
            'unread_notifications' => method_exists($user, 'unreadSystemNotifications')
                ? $user->unreadSystemNotifications()->count()
                : 0,
            default => 0,
        };
    }
}
