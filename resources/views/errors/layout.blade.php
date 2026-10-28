<!DOCTYPE html>
{{-- Standalone error page: no header, no database queries, no technical details — it must render even when the app cannot. --}}
<html lang="ar" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') — مِداد</title>
    <script @if (\Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}" @endif>(function () { var r = document.documentElement, c = 'system'; try { c = localStorage.getItem('athar-theme') || c; } catch (e) {} var dark = c === 'dark' || (c !== 'light' && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches); r.dataset.theme = dark ? 'dark' : 'light'; })();</script>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="inner-page error-page">
        <a class="brand" href="{{ url('/') }}" aria-label="مِداد، الصفحة الرئيسية"><span class="brand-logo"><span class="brand-mark">مِـداد</span><span class="brand-latin">MIDAD</span></span></a>
        <section class="error-card" aria-labelledby="error-title">
            <span class="error-code" aria-hidden="true">@yield('code')</span>
            <h1 id="error-title">@yield('title')</h1>
            <p>@yield('message')</p>
            <a class="button button-dark" href="{{ url('/') }}">العودة إلى الرئيسية <span aria-hidden="true">←</span></a>
        </section>
    </main>
</body>
</html>
