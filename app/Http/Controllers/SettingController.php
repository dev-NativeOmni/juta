<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Setting;
use App\Services\SchoolCalendar;
use App\Support\Signatures;
use App\Support\TargetRules;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'logo' => Setting::get('logo'),
            'nama_instansi' => Setting::get('nama_instansi'),
            'login_bg' => Setting::get('login_bg'),
            'landing_bg' => Setting::get('landing_bg'),
            'officials' => collect(Signatures::OFFICIALS)->map(fn ($official, $key) => [
                'label' => $official['label'],
                'name' => Signatures::officialIdentity($key)['name'],
                'preview' => Signatures::dataUri(Signatures::officialFile($key)),
            ])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'nama_instansi' => 'nullable|string|max:255',
            'login_bg' => 'nullable|image|max:5120',
            'landing_bg' => 'nullable|image|max:5120',
            'signatures' => 'nullable|array',
            'signatures.*' => Signatures::UPLOAD_RULES,
            'reset_signatures' => 'nullable|array',
            'reset_signatures.*' => 'in:'.implode(',', array_keys(Signatures::OFFICIALS)),
        ]);

        // Tanda tangan pejabat: hapus bila dicentang, ganti bila ada unggahan baru.
        foreach (array_keys(Signatures::OFFICIALS) as $key) {
            $settingKey = Signatures::OFFICIALS[$key]['file'];
            $upload = $request->file("signatures.{$key}");

            if ($upload || in_array($key, (array) $request->input('reset_signatures', []), true)) {
                Signatures::delete(Setting::get($settingKey));
                Setting::set($settingKey, $upload ? Signatures::store($upload, 'officials') : null);
            }
        }

        if ($request->boolean('reset_logo')) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('logo', null);
        } elseif ($request->hasFile('logo')) {
            $oldLogo = Setting::get('logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            $path = $request->file('logo')->store('settings', 'public');
            Setting::set('logo', $path);
        }

        if ($request->has('nama_instansi')) {
            Setting::set('nama_instansi', $request->input('nama_instansi'));
        }

        if ($request->boolean('reset_login_bg')) {
            $oldBg = Setting::get('login_bg');
            if ($oldBg) {
                Storage::disk('public')->delete($oldBg);
            }
            Setting::set('login_bg', null);
        } elseif ($request->hasFile('login_bg')) {
            $oldBg = Setting::get('login_bg');
            if ($oldBg) {
                Storage::disk('public')->delete($oldBg);
            }
            $path = $request->file('login_bg')->store('settings', 'public');
            Setting::set('login_bg', $path);
        }

        if ($request->boolean('reset_landing_bg')) {
            $oldLandingBg = Setting::get('landing_bg');
            if ($oldLandingBg) {
                Storage::disk('public')->delete($oldLandingBg);
            }
            Setting::set('landing_bg', null);
        } elseif ($request->hasFile('landing_bg')) {
            $oldLandingBg = Setting::get('landing_bg');
            if ($oldLandingBg) {
                Storage::disk('public')->delete($oldLandingBg);
            }
            $path = $request->file('landing_bg')->store('settings', 'public');
            Setting::set('landing_bg', $path);
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function editAdab()
    {
        $categories = Setting::getAdabQuestions();

        return view('settings.adab', compact('categories'));
    }

    public function updateAdab(Request $request)
    {
        $input = $request->input('categories', []);

        // Validate: must have at least 1 category, max 10
        if (count($input) < 1 || count($input) > 10) {
            return back()->withErrors(['categories' => 'Jumlah kategori minimal 1 dan maksimal 10.'])->withInput();
        }

        $rules = [];
        foreach ($input as $catIdx => $cat) {
            $rules["categories.{$catIdx}.title"] = 'required|string|max:255';
            $rules["categories.{$catIdx}.desc"] = 'required|string|max:1000';

            $questions = $cat['questions'] ?? [];
            foreach ($questions as $qIdx => $_) {
                $rules["categories.{$catIdx}.questions.{$qIdx}"] = 'required|string|max:500';
            }
        }

        $validated = $request->validate($rules);

        // Normalize: ensure questions are plain arrays (not keyed by q-number)
        $toSave = [];
        foreach ($validated['categories'] as $catIdx => $cat) {
            $toSave[] = [
                'title' => $cat['title'],
                'desc' => $cat['desc'],
                'questions' => array_values($cat['questions']),
            ];
        }

        Setting::set('adab_questions', json_encode($toSave));

        return redirect()->route('settings.adab')
            ->with('success', 'Daftar pertanyaan kuisioner adab berhasil diperbarui.');
    }

    public function calendarIndex(Request $request)
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('m'));

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $startDayOfWeek = $startDate->dayOfWeek;

        $gridDates = [];

        // Previous month padding (starting Sunday)
        for ($i = $startDayOfWeek; $i > 0; $i--) {
            $gridDates[] = [
                'date' => $startDate->copy()->subDays($i),
                'isCurrentMonth' => false,
            ];
        }

        // Current month days
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $gridDates[] = [
                'date' => $current->copy(),
                'isCurrentMonth' => true,
            ];
            $current->addDay();
        }

        // Next month padding to complete 7-column rows
        $remainder = count($gridDates) % 7;
        if ($remainder > 0) {
            $paddingCount = 7 - $remainder;
            for ($i = 0; $i < $paddingCount; $i++) {
                $gridDates[] = [
                    'date' => $endDate->copy()->addDays($i + 1),
                    'isCurrentMonth' => false,
                ];
            }
        }

        $prevCarbon = $startDate->copy()->subMonth();
        $nextCarbon = $startDate->copy()->addMonth();

        $prevMonth = $prevCarbon->month;
        $prevYear = $prevCarbon->year;
        $nextMonth = $nextCarbon->month;
        $nextYear = $nextCarbon->year;

        $calendar = app(SchoolCalendar::class);
        $monthDays = $calendar->monthDays($year, $month);
        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get();
        $locks = $calendar->monthLocks($year, $month);
        $permissions = $this->calendarPermissions($request, $year, $month);

        return view('settings.calendar', [
            'gridDates' => $gridDates,
            'year' => $year,
            'month' => $month,
            'globalDays' => $monthDays['global'],
            'classHolidays' => $monthDays['class'],
            'classRooms' => $classRooms,
            'locks' => $locks,
            'permissions' => $permissions,
            'adabDays' => $calendar->adabDays(),
            'prevMonth' => $prevMonth,
            'prevYear' => $prevYear,
            'nextMonth' => $nextMonth,
            'nextYear' => $nextYear,
        ]);
    }

    public function calendarUpdate(Request $request)
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('m'));
        $permissions = $this->calendarPermissions($request, $year, $month);

        abort_unless($permissions['edit_tahfizh'] || $permissions['edit_adab'], 403, 'Kalender bulan ini terkunci atau Anda tidak berhak mengubahnya.');

        $globalDays = [];
        foreach ((array) $request->input('days', []) as $date => $flags) {
            $globalDays[(string) $date] = [
                'tahfizh_off' => (bool) ($flags['tahfizh'] ?? false),
                'adab_off' => (bool) ($flags['adab'] ?? false),
            ];
        }
        // Format lama (holidays[] = Libur Total) tetap diterima.
        foreach ((array) $request->input('holidays', []) as $date) {
            $globalDays[(string) $date] = ['tahfizh_off' => true, 'adab_off' => true];
        }

        $classDays = array_filter(
            (array) $request->input('class_holidays', []),
            fn ($classIds, $date) => ! ($globalDays[$date]['tahfizh_off'] ?? false) && ! empty($classIds),
            ARRAY_FILTER_USE_BOTH
        );

        app(SchoolCalendar::class)->updateMonth(
            $year,
            $month,
            $globalDays,
            $classDays,
            $permissions['edit_tahfizh'],
            $permissions['edit_adab'],
            $request->user()?->id
        );

        return redirect()
            ->route('academic-calendar.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Kalender akademik berhasil diperbarui.');
    }

    /**
     * Hari pengisian kuisioner Adab (Admin & Koordinator Keagamaan).
     */
    public function calendarAdabDays(Request $request)
    {
        $validated = $request->validate([
            'adab_days' => ['required', 'array', 'min:1'],
            'adab_days.*' => ['integer', 'between:1,7'],
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer'],
        ], ['adab_days.required' => 'Pilih minimal satu hari pengisian Adab.']);
        abort_unless($this->calendarPermissions($request, (int) date('Y'), (int) date('n'))['edit_adab_days'], 403);

        app(SchoolCalendar::class)->saveAdabDays($validated['adab_days']);

        return redirect()
            ->route('academic-calendar.index', array_filter(['year' => $validated['year'] ?? null, 'month' => $validated['month'] ?? null]))
            ->with('success', 'Hari pengisian Adab diperbarui.');
    }

    public function calendarLock(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'scope' => ['required', 'in:'.SchoolCalendar::SCOPE_TAHFIZH.','.SchoolCalendar::SCOPE_ADAB],
            'action' => ['required', 'in:lock,unlock'],
        ]);
        $permissions = $this->calendarPermissions($request, $validated['year'], $validated['month']);
        $calendar = app(SchoolCalendar::class);
        $scopeLabel = $validated['scope'] === SchoolCalendar::SCOPE_TAHFIZH ? 'Tahfizh' : 'Adab';

        if ($validated['action'] === 'lock') {
            abort_unless($permissions['lock_'.$validated['scope']], 403);
            $calendar->lockMonth($validated['year'], $validated['month'], $validated['scope'], $request->user()?->id);
            $message = "Kalender {$scopeLabel} bulan ini dikunci.";
        } else {
            abort_unless($permissions['unlock'], 403, 'Hanya Super Admin & Admin yang bisa membuka kunci kalender.');
            $calendar->unlockMonth($validated['year'], $validated['month'], $validated['scope']);
            $message = "Kunci kalender {$scopeLabel} bulan ini dibuka.";
        }

        return redirect()
            ->route('academic-calendar.index', ['year' => $validated['year'], 'month' => $validated['month']])
            ->with('success', $message);
    }

    /**
     * Hak akses kalender: Super Admin & Admin mengatur semuanya; Koordinator Adab
     * (Koordinator Keagamaan, role supervisor) hanya status Adab & mengunci Adab. Bulan terkunci tidak bisa
     * diubah untuk cakupan itu sampai dibuka Super Admin/Admin.
     *
     * @return array<string, bool>
     */
    private function calendarPermissions(Request $request, int $year, int $month): array
    {
        $user = $request->user();
        $isAdmin = $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
        $isAdabCoordinator = $user?->hasRole('supervisor') ?? false;
        $calendar = app(SchoolCalendar::class);
        $tahfizhLocked = $calendar->isMonthLocked($year, $month, SchoolCalendar::SCOPE_TAHFIZH);
        $adabLocked = $calendar->isMonthLocked($year, $month, SchoolCalendar::SCOPE_ADAB);

        return [
            'is_admin' => $isAdmin,
            'edit_tahfizh' => $isAdmin && ! $tahfizhLocked,
            'edit_adab' => ($isAdmin || $isAdabCoordinator) && ! $adabLocked,
            'lock_tahfizh' => $isAdmin && ! $tahfizhLocked,
            'lock_adab' => ($isAdmin || $isAdabCoordinator) && ! $adabLocked,
            'unlock' => $isAdmin,
            'edit_adab_days' => $isAdmin || $isAdabCoordinator,
        ];
    }

    public function hafalanTargetsIndex()
    {
        $config = Setting::getHafalanTargetsConfig();

        return view('settings.hafalan-targets', [
            'config' => $config,
            'levelLines' => TargetRules::levelLines(),
            'mandatoryUntil' => TargetRules::mandatoryUntil(),
            'latestSwitch' => TargetRules::latestSwitch(),
            'canEditTargetRules' => request()->user()?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh']) ?? false,
        ]);
    }

    /**
     * Aturan target otomatis (baris per level, juz wajib, batas pindah ke depan), lalu
     * hitung ulang target otomatis triwulan berjalan untuk semua kelas 11/12.
     */
    public function targetRulesUpdate(Request $request)
    {
        $validated = $request->validate([
            'level_lines' => ['required', 'array'],
            'level_lines.*' => ['required', 'integer', 'between:1,60'],
            'mandatory_until' => ['required', 'integer', 'between:2,30'],
            'latest_switch' => ['required', 'integer', 'between:2,30', 'lte:mandatory_until'],
        ], [
            'latest_switch.lte' => 'Batas pindah paling akhir harus sama dengan atau setelah juz wajib (nomor juz lebih kecil atau sama).',
        ]);

        TargetRules::save($validated['level_lines'], (int) $validated['mandatory_until'], (int) $validated['latest_switch']);

        @set_time_limit(300);
        Artisan::call('tad:sync-auto-targets');

        return redirect()->route('settings.hafalan-targets')
            ->with('success', 'Aturan target otomatis disimpan dan target triwulan berjalan sudah dihitung ulang.');
    }

    public function hafalanTargetsUpdate(Request $request)
    {
        $inputConfig = $request->input('targets', []);

        $formattedConfig = [
            'grade_10' => [
                'tahfizh' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_10']['tahfizh']['target_juz_count'] ?? 4)),
                    'mode' => ($inputConfig['grade_10']['tahfizh']['mode'] ?? 'specific') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_10']['tahfizh']['specific_juz'] ?? []))),
                ],
                'reguler' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_10']['reguler']['target_juz_count'] ?? 2)),
                    'mode' => ($inputConfig['grade_10']['reguler']['mode'] ?? 'specific') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_10']['reguler']['specific_juz'] ?? []))),
                ],
            ],
            'grade_11' => [
                'tahfizh' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_11']['tahfizh']['target_juz_count'] ?? 4)),
                    'mode' => ($inputConfig['grade_11']['tahfizh']['mode'] ?? 'any') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_11']['tahfizh']['specific_juz'] ?? []))),
                ],
                'reguler' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_11']['reguler']['target_juz_count'] ?? 2)),
                    'mode' => ($inputConfig['grade_11']['reguler']['mode'] ?? 'any') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_11']['reguler']['specific_juz'] ?? []))),
                ],
            ],
            'grade_12' => [
                'tahfizh' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_12']['tahfizh']['target_juz_count'] ?? 4)),
                    'mode' => ($inputConfig['grade_12']['tahfizh']['mode'] ?? 'any') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_12']['tahfizh']['specific_juz'] ?? []))),
                ],
                'reguler' => [
                    'target_juz_count' => max(1, (int) ($inputConfig['grade_12']['reguler']['target_juz_count'] ?? 2)),
                    'mode' => ($inputConfig['grade_12']['reguler']['mode'] ?? 'any') === 'specific' ? 'specific' : 'any',
                    'specific_juz' => array_values(array_map('intval', (array) ($inputConfig['grade_12']['reguler']['specific_juz'] ?? []))),
                ],
            ],
        ];

        Setting::set('hafalan_targets_config', json_encode($formattedConfig));

        return redirect()
            ->route('settings.hafalan-targets')
            ->with('success', 'Konfigurasi target progres hafalan berhasil disimpan.');
    }

    public function hafalanTargetsReset()
    {
        Setting::set('hafalan_targets_config', null);

        return redirect()
            ->route('settings.hafalan-targets')
            ->with('success', 'Konfigurasi target progres hafalan berhasil di-reset ke standar default.');
    }

    public function tahfizhScoringIndex()
    {
        return view('settings.tahfizh-scoring', [
            'config' => Setting::getTahfizhScoringConfig(),
        ]);
    }

    public function tahfizhScoringUpdate(Request $request)
    {
        $validated = $request->validate([
            'target_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'target_incomplete_score' => ['required', 'integer', 'min:0'],
        ]);

        if ($validated['target_weight'] + $validated['exam_weight'] !== 100) {
            return redirect()
                ->route('settings.tahfizh-scoring')
                ->withErrors(['target_weight' => 'Total bobot ketuntasan target dan ujian harus sama dengan 100.'])
                ->withInput();
        }

        if ($validated['target_incomplete_score'] > $validated['target_weight']) {
            return redirect()
                ->route('settings.tahfizh-scoring')
                ->withErrors(['target_incomplete_score' => 'Nilai target belum tuntas tidak boleh melebihi bobot ketuntasan target.'])
                ->withInput();
        }

        Setting::set('tahfizh_scoring_config', json_encode($validated));

        return redirect()
            ->route('settings.tahfizh-scoring')
            ->with('success', 'Pengaturan penilaian tahfizh berhasil disimpan.');
    }

    public function tahfizhScoringReset()
    {
        Setting::set('tahfizh_scoring_config', null);

        return redirect()
            ->route('settings.tahfizh-scoring')
            ->with('success', 'Pengaturan penilaian tahfizh berhasil di-reset ke standar default.');
    }
}
