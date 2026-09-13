<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HafizPlus API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi ini dipakai untuk hardening API v1:
    | - rate limit API umum
    | - rate limit login
    | - token expiration policy
    | - max active tokens per user
    | - allowed CORS origins
    |
    */

    'api' => [
        'rate_limit_per_minute' => (int) env('HAFIZPLUS_API_RATE_LIMIT_PER_MINUTE', 60),

        'login_rate_limit_per_minute' => (int) env('HAFIZPLUS_API_LOGIN_RATE_LIMIT_PER_MINUTE', 5),

        'token_expiration_days' => (int) env('HAFIZPLUS_API_TOKEN_EXPIRATION_DAYS', 30),

        'max_active_tokens_per_user' => (int) env('HAFIZPLUS_API_MAX_ACTIVE_TOKENS_PER_USER', 5),

        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'HAFIZPLUS_CORS_ALLOWED_ORIGINS',
                'http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173'
            ))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Role-role di bawah ini wajib mengaktifkan 2FA sebelum bisa mengakses
    | halaman lain di aplikasi. `enforce` dimatikan secara default di
    | environment testing (lihat .env.testing) supaya test suite yang ada
    | tidak perlu mengaktifkan 2FA di setiap fixture user super admin;
    | perilaku wajib-2FA sendiri diuji secara eksplisit di
    | tests/Feature/TwoFactorAuthenticationTest.php dengan meng-override
    | config ini di dalam test tersebut.
    |
    */

    'two_factor' => [
        'enforce' => (bool) env('HAFIZPLUS_2FA_ENFORCE', true),

        'required_roles' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HAFIZPLUS_2FA_REQUIRED_ROLES', 'super_admin'))
        ))),
    ],

];
