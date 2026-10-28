<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f5f2eb">
    <meta name="description" content="مِداد | MIDAD منصة عربية تفاعلية لنشر الاقتباسات والشعر وإدارتها ومشاركتها.">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="flash-status" content="{{ session('status') }}">
    <title>@yield('title', 'مِداد | MIDAD')</title>
    @stack('head')
    {{-- Resolve the theme before first paint: saved choice (light/dark) or the system setting (default). --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">(function () { var r = document.documentElement, c = 'system'; try { c = localStorage.getItem('athar-theme') || c; } catch (e) {} if (c !== 'light' && c !== 'dark') c = 'system'; var dark = c === 'dark' || (c === 'system' && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches); r.dataset.theme = dark ? 'dark' : 'light'; r.dataset.themeChoice = c; var m = document.querySelector('meta[name="theme-color"]'); if (m) m.content = dark ? '#1b1a17' : '#f5f2eb'; })();</script>
    {{-- The two faces every page paints with first; the rest load on demand (font-display: swap). --}}
    <link rel="preload" href="/fonts/thmanyah/thmanyahsans-Regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/thmanyah/thmanyahseriftext-Regular.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="site-shell">
        <x-site-header />

        @if ($errors->any())
            <div class="form-alert global-alert" role="alert">{{ $errors->first() }}</div>
        @endif
        @yield('content')

        <x-site-footer />
    </div>
    <x-confirm-dialog />
    @stack('dialogs')
    @stack('scripts')
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
