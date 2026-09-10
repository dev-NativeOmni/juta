<?php

use App\Http\Controllers\AdabController;
use App\Http\Controllers\AdabMaterialController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\HafalanRecordController;
use App\Http\Controllers\HafalanTargetController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\MurajaahRecordController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuarterlyReportController;
use App\Http\Controllers\QuickInputController;
use App\Http\Controllers\QuranMushafController;
use App\Http\Controllers\QuranPdfController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SpreadsheetInputController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPointController;
use App\Http\Controllers\StudentReportController;
use App\Http\Controllers\SuperAdmin\UserManagementController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TahfizhExamController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Role Switcher Route
    |--------------------------------------------------------------------------
    */
    Route::post('/role/switch', [RoleSwitchController::class, 'switch'])->name('role.switch');
    Route::get('/role/switch/{role}', [RoleSwitchController::class, 'switch'])->name('role.switch.get');

    /*
    |--------------------------------------------------------------------------
    | Session Keep-Alive Route
    |--------------------------------------------------------------------------
    */
    Route::get('/keep-alive', function () {
        return response()->json([
            'status' => 'ok',
            'csrf' => csrf_token(),
        ]);
    })->name('keep-alive');

    Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'redirect'])
        ->name('dashboard');

    Route::get('/super-admin/dashboard', [DashboardController::class, 'superAdmin'])
        ->middleware('role:super_admin')
        ->name('super-admin.dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:super_admin,admin,teacher,supervisor,headmaster,tanse,coordinator_tahfizh,pendamping_adab')
        ->name('admin.dashboard');

    Route::get('/teacher/dashboard', [DashboardController::class, 'teacher'])
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::get('/parent/dashboard', [DashboardController::class, 'parent'])
        ->middleware('role:parent')
        ->name('parent.dashboard');

    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')
        ->name('student.dashboard');

    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisor'])
        ->middleware('role:supervisor')
        ->name('supervisor.dashboard');

    Route::get('/coordinator-tahfizh/dashboard', [DashboardController::class, 'coordinatorTahfizh'])
        ->middleware('role:coordinator_tahfizh,super_admin,admin')
        ->name('coordinator-tahfizh.dashboard');

    Route::get('/pendamping-adab/dashboard', [DashboardController::class, 'pendampingAdab'])
        ->middleware('role:pendamping_adab,super_admin,admin')
        ->name('pendamping-adab.dashboard');

    Route::get('/tanse/dashboard', [DashboardController::class, 'tanse'])
        ->middleware('role:tanse,super_admin,admin')
        ->name('tanse.dashboard');

    Route::get('/headmaster/dashboard', [DashboardController::class, 'headmaster'])
        ->middleware('role:headmaster,super_admin,admin')
        ->name('headmaster.dashboard');

    /*
    |--------------------------------------------------------------------------
    | System Notifications
    |--------------------------------------------------------------------------
    | Index/show/read/delete boleh diakses semua user login.
    | Create/store/edit/update hanya admin dan super admin.
    |--------------------------------------------------------------------------
    */

    Route::prefix('system-notifications')
        ->name('system-notifications.')
        ->group(function () {
            Route::get('/', [SystemNotificationController::class, 'index'])
                ->name('index');

            Route::patch('/mark-all-read', [SystemNotificationController::class, 'markAllAsRead'])
                ->name('mark-all-read');

            Route::middleware(['role:super_admin,admin'])->group(function () {
                Route::get('/create', [SystemNotificationController::class, 'create'])
                    ->name('create');

                Route::post('/', [SystemNotificationController::class, 'store'])
                    ->name('store');

                Route::get('/{systemNotification}/edit', [SystemNotificationController::class, 'edit'])
                    ->name('edit');

                Route::patch('/{systemNotification}', [SystemNotificationController::class, 'update'])
                    ->name('update');
            });

            Route::get('/{systemNotification}', [SystemNotificationController::class, 'show'])
                ->name('show');

            Route::patch('/{systemNotification}/mark-as-read', [SystemNotificationController::class, 'markAsRead'])
                ->name('mark-as-read');

            Route::delete('/{systemNotification}', [SystemNotificationController::class, 'destroy'])
                ->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Area
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin'])->group(function () {
        // Programs
        Route::get('programs/export', [ProgramController::class, 'export'])->name('programs.export');
        Route::post('programs/import', [ProgramController::class, 'import'])->name('programs.import');
        Route::resource('programs', ProgramController::class);

        Route::get('class-rooms/export', [ClassRoomController::class, 'export'])->name('class-rooms.export');
        Route::post('class-rooms/import', [ClassRoomController::class, 'import'])->name('class-rooms.import');
        Route::get('class-rooms/{class_room}/export-capaian', [ClassRoomController::class, 'exportCapaian'])->name('class-rooms.export-capaian');
        Route::resource('class-rooms', ClassRoomController::class);
        // Badges Management (Super Admin / Admin / Koordinator Tahfizh)
    });

    Route::middleware(['role:super_admin,admin,coordinator_tahfizh'])->group(function () {
        Route::resource('badges', BadgeController::class);
        Route::post('badges/{badge}/toggle', [BadgeController::class, 'toggleActive'])->name('badges.toggle');
    });

    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::get('class-schedules', [ClassRoomController::class, 'scheduleIndex'])->name('class-schedules.index');
        Route::post('class-schedules/update', [ClassRoomController::class, 'scheduleUpdate'])->name('class-schedules.update');
        Route::get('academic-calendar', [SettingController::class, 'calendarIndex'])->name('academic-calendar.index');
        Route::post('academic-calendar/update', [SettingController::class, 'calendarUpdate'])->name('academic-calendar.update');

        // Teachers
        Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
        Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
        Route::resource('teachers', TeacherController::class);

        // Parents
        Route::get('parents/export', [ParentController::class, 'export'])->name('parents.export');
        Route::post('parents/import', [ParentController::class, 'import'])->name('parents.import');
        Route::resource('parents', ParentController::class);

        // Students
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
        Route::resource('students', StudentController::class);

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit-logs.show');

        Route::prefix('database-backups')
            ->name('database-backups.')
            ->group(function () {
                Route::get('/', [DatabaseBackupController::class, 'index'])
                    ->name('index');

                Route::post('/', [DatabaseBackupController::class, 'store'])
                    ->name('store');

                Route::get('/{filename}/download', [DatabaseBackupController::class, 'download'])
                    ->name('download');

                Route::delete('/{filename}', [DatabaseBackupController::class, 'destroy'])
                    ->name('destroy');
            });
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('users/{user}/link-parents', [UserController::class, 'linkParents'])->name('users.link-parents');
        Route::post('users/{user}/link-students', [UserController::class, 'linkStudents'])->name('users.link-students');
        Route::post('impersonate/{user}', [ImpersonateController::class, 'start'])->name('impersonate.start');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware(['role:super_admin,admin,supervisor,coordinator_tahfizh'])->group(function () {
        Route::get('/pengaturan-adab', [SettingController::class, 'editAdab'])->name('settings.adab');
        Route::post('/pengaturan-adab', [SettingController::class, 'updateAdab'])->name('settings.adab.update');
        Route::get('/hafalan-target-settings', [SettingController::class, 'hafalanTargetsIndex'])->name('settings.hafalan-targets');
        Route::post('/hafalan-target-settings', [SettingController::class, 'hafalanTargetsUpdate'])->name('settings.hafalan-targets.update');
        Route::post('/hafalan-target-settings/reset', [SettingController::class, 'hafalanTargetsReset'])->name('settings.hafalan-targets.reset');
    });

    // Super Admin user management routes
    Route::prefix('superadmin')->name('superadmin.')->middleware(['role:super_admin'])->group(function () {
        Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('users/{id}/force-reset', [UserManagementController::class, 'forceReset'])->name('users.force-reset');
    });

    /*
    |--------------------------------------------------------------------------
    | Hafalan, Murajaah, Target, Quick Input
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin,teacher,headmaster,coordinator_tahfizh'])->group(function () {
        Route::resource('hafalan-records', HafalanRecordController::class);

        Route::get('murajaah-records/fast-input', [MurajaahRecordController::class, 'fastInput'])
            ->name('murajaah-records.fast-input');
        Route::post('murajaah-records/fast-store', [MurajaahRecordController::class, 'fastStore'])
            ->name('murajaah-records.fast-store');

        Route::resource('murajaah-records', MurajaahRecordController::class);
        Route::resource('tahfizh-exams', TahfizhExamController::class);

        Route::post('/hafalan-targets/store-bulk-reguler', [HafalanTargetController::class, 'storeBulkReguler'])
            ->name('hafalan-targets.store-bulk-reguler');
        Route::post('/hafalan-targets/store-bulk-ummi', [HafalanTargetController::class, 'storeBulkUmmi'])
            ->name('hafalan-targets.store-bulk-ummi');

        Route::post('/hafalan-targets/bulk-complete', [HafalanTargetController::class, 'bulkComplete'])
            ->name('hafalan-targets.bulk-complete');
        Route::post('/hafalan-targets/bulk-destroy', [HafalanTargetController::class, 'bulkDestroy'])
            ->name('hafalan-targets.bulk-destroy');

        Route::patch('/hafalan-targets/{hafalanTarget}/complete', [HafalanTargetController::class, 'complete'])
            ->name('hafalan-targets.complete');

        Route::patch('/hafalan-targets/{hafalanTarget}/mark-missed', [HafalanTargetController::class, 'markMissed'])
            ->name('hafalan-targets.mark-missed');

        Route::resource('hafalan-targets', HafalanTargetController::class);

        Route::post('/ummi-records', [QuickInputController::class, 'storeUmmi'])
            ->name('ummi-records.store');
        Route::get('/ummi-records/{ummiRecord}/edit', [HafalanRecordController::class, 'editUmmi'])
            ->name('ummi-records.edit');
        Route::put('/ummi-records/{ummiRecord}', [HafalanRecordController::class, 'updateUmmi'])
            ->name('ummi-records.update');
        Route::delete('/ummi-records/{ummiRecord}', [HafalanRecordController::class, 'destroyUmmi'])
            ->name('ummi-records.destroy');
        Route::post('/hafalan-records/bulk-destroy', [HafalanRecordController::class, 'bulkDestroy'])
            ->name('hafalan-records.bulk-destroy');
        Route::post('/ummi-records/bulk-destroy', [HafalanRecordController::class, 'bulkDestroyUmmi'])
            ->name('ummi-records.bulk-destroy');

        Route::get('/spreadsheet-input', [SpreadsheetInputController::class, 'index'])
            ->name('spreadsheet-input.index');
        Route::post('/spreadsheet-input/save', [SpreadsheetInputController::class, 'save'])
            ->name('spreadsheet-input.save');
    });

    /*
    |--------------------------------------------------------------------------
    | Student Points & Discipline (Tanse / Ketahanan Sekolah)
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin,teacher,parent,student,headmaster,tanse'])->group(function () {
        Route::get('/student-points', [StudentPointController::class, 'index'])->name('student-points.index');
        Route::get('/student-points/chart', [StudentPointController::class, 'chart'])->name('student-points.chart');
    });

    Route::middleware(['role:super_admin,admin,tanse'])->group(function () {
        Route::resource('student-points', StudentPointController::class)->except(['index']);
    });

    /*
    |--------------------------------------------------------------------------
    | Progress & Reports
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin,teacher,parent,student,headmaster,supervisor,coordinator_tahfizh'])->group(function () {
        Route::get('/hafalan-records/student/{student}/ummi-card', [HafalanRecordController::class, 'ummiCard'])
            ->name('hafalan-records.student.ummi-card');

        Route::get('/class-rooms/{class_room}/print-ummi-cards', [ClassRoomController::class, 'printUmmiCards'])
            ->name('class-rooms.print-ummi-cards');

        Route::get('/progress', [ProgressController::class, 'index'])
            ->name('progress.index');

        Route::get('/progress/{student}', [ProgressController::class, 'show'])
            ->name('progress.show');

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/teachers', [ReportController::class, 'teacherPerformance'])
            ->middleware('role:super_admin,admin,headmaster')
            ->name('reports.teachers');

        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])
            ->name('reports.export.csv');

        Route::get('/reports/periodic', [ReportController::class, 'periodicProgress'])
            ->middleware('role:super_admin,admin,teacher,headmaster,coordinator_tahfizh,supervisor')
            ->name('reports.periodic');

        Route::get('/reports/whatsapp', [ReportController::class, 'whatsappDaily'])
            ->middleware('role:super_admin,admin,teacher,headmaster,coordinator_tahfizh,supervisor')
            ->name('reports.whatsapp');

        Route::get('/attendances/check', [AttendanceController::class, 'check'])
            ->middleware('role:super_admin,admin,teacher,coordinator_tahfizh,supervisor')
            ->name('attendances.check');

        Route::post('/attendances/save', [AttendanceController::class, 'save'])
            ->middleware('role:super_admin,admin,teacher,coordinator_tahfizh,supervisor')
            ->name('attendances.save');

        Route::get('/admin/reports/quarterly', [QuarterlyReportController::class, 'index'])
            ->middleware('role:super_admin,admin')
            ->name('reports.quarterly');

        Route::get('/reports/periodic/print', [ReportController::class, 'periodicProgressPrint'])
            ->middleware('role:super_admin,admin,teacher,headmaster,coordinator_tahfizh,supervisor')
            ->name('reports.periodic.print');

        Route::get('/reports/student/{student}', [ReportController::class, 'student'])
            ->name('reports.student');

        Route::get('/reports/student/{student}/export/csv', [ReportController::class, 'exportStudentCsv'])
            ->name('reports.student.export.csv');
    });

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::get('/quran-pdf', [QuranPdfController::class, 'index'])
        ->name('quran.pdf');

    Route::post('/quran-pdf/config', [QuranPdfController::class, 'updateConfig'])
        ->middleware('role:super_admin,admin')
        ->name('quran.pdf.config');

    Route::get('/mushaf', [QuranMushafController::class, 'index'])
        ->name('quran.mushaf');

    /*
    |--------------------------------------------------------------------------
    | Adab
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,supervisor,teacher,parent,student,pendamping_adab,headmaster'])->group(function () {
        Route::get('/adab', [AdabController::class, 'index'])->name('adab.index');
        Route::get('/adab/chart', [AdabController::class, 'monthlyChart'])->name('adab.chart');
        Route::get('/adab/student/{student}', [AdabController::class, 'show'])->name('adab.show');
        Route::get('/adab/student/{student}/create', [AdabController::class, 'create'])->name('adab.create');
        Route::post('/adab/student/{student}', [AdabController::class, 'store'])->name('adab.store');
        Route::post('/adab/student/{student}/mentor-score', [AdabController::class, 'storeMentorScore'])->name('adab.store-mentor-score');
        Route::post('/adab/batch-mentor-score', [AdabController::class, 'batchStoreMentorScores'])->name('adab.batch-mentor-score');
        Route::get('/adab/mentor-class-data', [AdabController::class, 'getMentorClassData'])->name('adab.mentor-class-data');

        Route::middleware(['role:super_admin,admin,supervisor'])->group(function () {
            Route::delete('/adab/{adabRecord}', [AdabController::class, 'destroy'])->name('adab.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Rapor Digital Terpadu
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,teacher,coordinator_tahfizh,tanse'])->group(function () {
        Route::get('/digital-reports', [StudentReportController::class, 'index'])->name('digital-reports.index');
        Route::get('/digital-reports/student/{student}', [StudentReportController::class, 'show'])->name('digital-reports.show');
        Route::get('/digital-reports/student/{student}/print', [StudentReportController::class, 'print'])->name('digital-reports.print');
        Route::get('/digital-reports/class/{classRoom}/print', [StudentReportController::class, 'printClass'])->name('digital-reports.class-print');
        Route::get('/digital-reports/settings', [StudentReportController::class, 'settings'])->name('digital-reports.settings');
        Route::post('/digital-reports/settings', [StudentReportController::class, 'updateSettings'])->name('digital-reports.settings.update');
        Route::post('/digital-reports/student/{student}', [StudentReportController::class, 'update'])->name('digital-reports.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Materi Adab
    |--------------------------------------------------------------------------
    |
    */
    Route::middleware(['role:super_admin,admin,teacher,supervisor,headmaster,tanse,coordinator_tahfizh,pendamping_adab'])->group(function () {
        Route::get('/materi-adab', [AdabMaterialController::class, 'index'])->name('adab-materials.index');
    });

    Route::middleware(['role:super_admin,admin,teacher,supervisor,coordinator_tahfizh,pendamping_adab,headmaster'])->group(function () {
        Route::get('/materi-adab/create', [AdabMaterialController::class, 'create'])->name('adab-materials.create');
        Route::post('/materi-adab', [AdabMaterialController::class, 'store'])->name('adab-materials.store');
        Route::get('/materi-adab/{adabMaterial}/edit', [AdabMaterialController::class, 'edit'])->name('adab-materials.edit');
        Route::put('/materi-adab/{adabMaterial}', [AdabMaterialController::class, 'update'])->name('adab-materials.update');
        Route::delete('/materi-adab/{adabMaterial}', [AdabMaterialController::class, 'destroy'])->name('adab-materials.destroy');
    });
});

require __DIR__.'/auth.php';
/*
|--------------------------------------------------------------------------
| Local Development API Tester
|--------------------------------------------------------------------------
|
| Hanya aktif di APP_ENV=local.
| Jangan dipakai sebagai halaman publik production.
|
*/
if (app()->environment('local', 'testing')) {
    Route::get('/dev/api-tester', function () {
        return view('dev.api-tester');
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.api-tester');

    Route::get('/dev/api-docs', function () {
        return view('dev.api-docs');
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.api-docs');

    Route::get('/dev/openapi.yaml', function () {
        $path = base_path('docs/api-v1-openapi.yaml');
        if (! file_exists($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/yaml',
        ]);
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.openapi-yaml');
}

Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/'.$path);

    if (! file_exists($filePath)) {
        abort(404);
    }

    $mimeType = mime_content_type($filePath) ?: 'image/png';

    return response()->file($filePath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*')->name('storage.fallback');
