{{--
    Favicon, ikon layar utama iOS, dan manifest PWA Android -- semuanya dari monogram
    logo TAD (public/icons, dibuat dari public/images/logo-tad.png; tagline tidak
    dipakai karena tidak terbaca di ukuran ikon).
    Naikkan ?v= bila berkas ikon diganti, supaya browser/HP tidak memakai cache lama.
--}}
<link rel="manifest" href="/manifest.json?v=3">
<meta name="theme-color" content="{{ $themeColor ?? '#059669' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="TAD SMAIA 7">
<meta name="application-name" content="TAD SMAIA 7">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png?v=3">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png?v=3">
<link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png?v=3">
<link rel="icon" href="/favicon.ico?v=3" sizes="any">
