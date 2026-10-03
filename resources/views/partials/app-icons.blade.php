@php
    $activeInstitution = app(\App\Services\InstitutionContext::class)->get();
    $appName = $activeInstitution?->name ?? \App\Models\Setting::get('app_name', config('app.name', 'JUTA'));
    $customFavicon = \App\Models\Setting::get('favicon');
    $faviconUrl = $customFavicon ? asset('storage/' . $customFavicon) : null;
@endphp
<link rel="manifest" href="/manifest.json?v=4">
<meta name="theme-color" content="{{ $themeColor ?? '#059669' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ $appName }}">
<meta name="application-name" content="{{ $appName }}">

@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@else
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png?v=3">
    <link rel="icon" href="/favicon.ico?v=3" sizes="any">
@endif

