<?php

namespace App\Providers;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Attendance;
use App\Models\Badge;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use App\Models\TahfizhExam;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Observers\HafalanRecordObserver;
use App\Observers\ModelAuditObserver;
use App\Policies\HafalanRecordPolicy;
use App\Policies\HafalanTargetPolicy;
use App\Policies\MurajaahRecordPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TahfizhExamPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        /*
        |--------------------------------------------------------------------------
        | API Rate Limiting
        |--------------------------------------------------------------------------
        */
        $this->configureRateLimiting();

        /*
        |--------------------------------------------------------------------------
        | Domain Observers
        |--------------------------------------------------------------------------
        */
        HafalanRecord::observe(HafalanRecordObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Audit Observers
        |--------------------------------------------------------------------------
        */
        User::observe(ModelAuditObserver::class);
        Program::observe(ModelAuditObserver::class);
        ClassRoom::observe(ModelAuditObserver::class);
        TeacherProfile::observe(ModelAuditObserver::class);
        ParentProfile::observe(ModelAuditObserver::class);
        Student::observe(ModelAuditObserver::class);
        MurajaahRecord::observe(ModelAuditObserver::class);
        HafalanTarget::observe(ModelAuditObserver::class);
        TahfizhExam::observe(ModelAuditObserver::class);
        Attendance::observe(ModelAuditObserver::class);
        StudentPoint::observe(ModelAuditObserver::class);
        StudentReport::observe(ModelAuditObserver::class);
        AdabRecord::observe(ModelAuditObserver::class);
        AdabMentorAssessment::observe(ModelAuditObserver::class);
        Badge::observe(ModelAuditObserver::class);
        Setting::observe(ModelAuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Policies
        |--------------------------------------------------------------------------
        */
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(HafalanRecord::class, HafalanRecordPolicy::class);
        Gate::policy(MurajaahRecord::class, MurajaahRecordPolicy::class);
        Gate::policy(HafalanTarget::class, HafalanTargetPolicy::class);
        Gate::policy(TahfizhExam::class, TahfizhExamPolicy::class);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $maxAttempts = max(
                1,
                (int) config('hafizplus.api.rate_limit_per_minute', 60)
            );

            $user = $request->user();
            $key = $user
                ? 'user:'.$user->id
                : 'ip:'.$request->ip();

            return Limit::perMinute($maxAttempts)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak request. Silakan coba lagi beberapa saat.',
                        'data' => null,
                        'errors' => [
                            'rate_limit' => [
                                'API rate limit exceeded.',
                            ],
                        ],
                    ], 429, $headers);
                });
        });

        RateLimiter::for('api-login', function (Request $request) {
            $maxAttempts = max(
                1,
                (int) config('hafizplus.api.login_rate_limit_per_minute', 5)
            );

            $email = Str::lower((string) $request->input('email', 'guest'));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute($maxAttempts)
                    ->by('login:'.$email.'|'.$ip)
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Percobaan login terlalu banyak. Silakan coba lagi beberapa saat.',
                            'data' => null,
                            'errors' => [
                                'login' => [
                                    'Login rate limit exceeded.',
                                ],
                            ],
                        ], 429, $headers);
                    }),

                Limit::perMinute(20)
                    ->by('login-ip:'.$ip)
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Terlalu banyak percobaan login dari alamat ini.',
                            'data' => null,
                            'errors' => [
                                'login' => [
                                    'IP login rate limit exceeded.',
                                ],
                            ],
                        ], 429, $headers);
                    }),
            ];
        });

        RateLimiter::for('two-factor-challenge', function (Request $request) {
            $userId = $request->session()->get('two_factor.user_id', 'guest');

            return Limit::perMinute(5)->by('two-factor:'.$userId.'|'.$request->ip());
        });
    }
}
