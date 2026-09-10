<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'nama_instansi' => 'nullable|string|max:255',
            'login_bg' => 'nullable|image|max:5120',
            'landing_bg' => 'nullable|image|max:5120',
        ]);

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

        $holidays = Setting::getNationalHolidays($year);
        $classRooms = ClassRoom::query()->orderBy('name')->get();

        $classHolidaysRaw = Setting::get("class_holidays_{$year}");
        $classHolidays = $classHolidaysRaw ? json_decode($classHolidaysRaw, true) : [];

        return view('settings.calendar', compact(
            'gridDates', 'year', 'month', 'holidays', 'classRooms', 'classHolidays',
            'prevMonth', 'prevYear', 'nextMonth', 'nextYear'
        ));
    }

    public function calendarUpdate(Request $request)
    {
        $year = $request->integer('year', (int) date('Y'));
        $month = $request->integer('month', (int) date('m'));
        $submittedHolidays = $request->input('holidays', []);
        $submittedClassHolidays = $request->input('class_holidays', []);

        // 1. Merge global holidays
        $existingHolidays = Setting::getNationalHolidays($year);
        $monthPrefix = sprintf('%04d-%02d-', $year, $month);
        $otherMonthsHolidays = array_filter($existingHolidays, function ($date) use ($monthPrefix) {
            return strpos($date, $monthPrefix) !== 0;
        });
        $allHolidays = array_merge($otherMonthsHolidays, $submittedHolidays);
        sort($allHolidays);
        Setting::set("national_holidays_{$year}", json_encode(array_values(array_unique($allHolidays))));

        // 2. Merge class-specific holidays
        $existingClassHolidaysRaw = Setting::get("class_holidays_{$year}");
        $existingClassHolidays = $existingClassHolidaysRaw ? json_decode($existingClassHolidaysRaw, true) : [];

        $otherMonthsClassHolidays = [];
        foreach ($existingClassHolidays as $dateStr => $classIds) {
            if (strpos($dateStr, $monthPrefix) !== 0) {
                $otherMonthsClassHolidays[$dateStr] = $classIds;
            }
        }

        $filteredNewClassHolidays = [];
        foreach ($submittedClassHolidays as $dateStr => $classIds) {
            if (! empty($classIds)) {
                $filteredNewClassHolidays[$dateStr] = array_map('intval', $classIds);
            }
        }

        $allClassHolidays = array_merge($otherMonthsClassHolidays, $filteredNewClassHolidays);
        ksort($allClassHolidays);
        Setting::set("class_holidays_{$year}", json_encode($allClassHolidays));

        return redirect()
            ->route('academic-calendar.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Kalender akademik berhasil diperbarui.');
    }

    public function hafalanTargetsIndex()
    {
        $config = Setting::getHafalanTargetsConfig();

        return view('settings.hafalan-targets', [
            'config' => $config,
        ]);
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
}
