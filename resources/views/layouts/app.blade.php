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
    <script>try { var t = localStorage.getItem('athar-theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}</script>
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
    @stack('dialogs')
    @stack('scripts')
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
