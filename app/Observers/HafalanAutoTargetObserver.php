<?php

namespace App\Observers;

use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Services\AutoHafalanTargetService;
use Carbon\Carbon;

/**
 * Menyegarkan target hafalan otomatis begitu setoran dibuat/diubah/dihapus, sehingga
 * target bulanan langsung menyesuaikan (mis. saat setoran bulan pertama term baru
 * diinput). Sinkronisasi ditunda sampai akhir request dan digabung per murid+term supaya
 * simpan massal (spreadsheet) tidak memicu sinkron berulang. Di luar request web
 * (console, seeder, tinker) tidak berjalan -- gunakan tad:sync-auto-targets.
 */
class HafalanAutoTargetObserver
{
    /** @var array<string, array{student_id: int, date: string}> */
    private static array $pending = [];

    private static ?int $registeredFor = null;

    public function saved(HafalanRecord|HafalanRecordSurah $model): void
    {
        $this->mark($model, includeOriginal: true);
    }

    public function deleted(HafalanRecord|HafalanRecordSurah $model): void
    {
        $this->mark($model);
    }

    public function restored(HafalanRecord|HafalanRecordSurah $model): void
    {
        $this->mark($model);
    }

    private function mark(HafalanRecord|HafalanRecordSurah $model, bool $includeOriginal = false): void
    {
        // Hanya saat ada request web yang sedang berjalan (bukan console/seeder/tinker).
        if (! app()->bound('request') || ! request()->route()) {
            return;
        }

        $header = $model instanceof HafalanRecordSurah
            ? HafalanRecord::withTrashed()->find($model->hafalan_record_id)
            : $model;

        if (! $header) {
            return;
        }

        $this->queue((int) $header->student_id, $header->submitted_at);

        // Setoran dipindah murid/tanggal: term lama juga perlu dihitung ulang.
        if ($includeOriginal && $model instanceof HafalanRecord && $model->wasChanged(['student_id', 'submitted_at'])) {
            $this->queue(
                (int) ($model->getOriginal('student_id') ?? $model->student_id),
                $model->getOriginal('submitted_at') ?? $model->submitted_at
            );
        }
    }

    private function queue(int $studentId, mixed $date): void
    {
        if (! $studentId || ! $date) {
            return;
        }

        $dateString = Carbon::parse($date)->toDateString();
        $key = "{$studentId}|".substr($dateString, 0, 7);
        self::$pending[$key] = ['student_id' => $studentId, 'date' => $dateString];

        // Satu callback per instance aplikasi (instance baru per test/request worker).
        if (self::$registeredFor !== spl_object_id(app())) {
            self::$registeredFor = spl_object_id(app());
            self::$pending = [$key => self::$pending[$key]];
            app()->terminating(function () {
                self::flush();
            });
        }
    }

    public static function flush(): void
    {
        $pending = self::$pending;
        self::$pending = [];
        self::$registeredFor = null;

        if ($pending === []) {
            return;
        }

        $service = app(AutoHafalanTargetService::class);

        foreach ($pending as $item) {
            try {
                $student = Student::query()->find($item['student_id']);

                if ($student) {
                    $service->syncStudent($student, Carbon::parse($item['date']));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
