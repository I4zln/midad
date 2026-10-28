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
    <dialog class="welcome-dialog" id="ethraa-welcome" aria-labelledby="ethraa-welcome-title" aria-describedby="ethraa-welcome-copy">
        <article class="welcome-card">
            <button class="welcome-close" type="button" data-welcome-close aria-label="إغلاق وبدء التصفح">×</button>
            <div class="welcome-card-heading"><span class="welcome-seal" aria-hidden="true">م</span><span class="eyebrow"><span class="eyebrow-line"></span> مِداد · مساحة للكلمات</span></div>
            <div class="welcome-content">
                <h1 id="ethraa-welcome-title" class="sr-only">رسالة ترحيب من عبدالعزيز تركي المطرفي</h1>
                <div class="welcome-typing-area" aria-hidden="true">
                    <div class="welcome-message-placeholder">
                        <p>السلام عليكم ورحمة الله وبركاته،</p>
                        <p>شكرًا لكل من جرّب مِداد وشاركنا ملاحظاته. بفضل اقتراحاتكم، نواصل تحسين تجربة القراءة وإضافة الاقتباسات.</p>
                        <p>اختر الأجواء التي تناسبك، واستمتع برحلتك بين الكلمات.</p>
                    </div>
                    <div class="welcome-message-output"><p></p><p></p><p></p></div>
                </div>
        <div class="welcome-screenreader-copy sr-only" id="ethraa-welcome-copy">
                    <p>السلام عليكم ورحمة الله وبركاته،</p>
                    <p>شكرًا لكل من جرّب مِداد وشاركنا ملاحظاته. بفضل اقتراحاتكم، نواصل تحسين تجربة القراءة وإضافة الاقتباسات.</p>
                    <p>اختر الأجواء التي تناسبك، واستمتع برحلتك بين الكلمات.</p>
                    <p>عبدالعزيز تركي المطرفي</p>
                </div>
                <p class="welcome-signature">عبدالعزيز تركي المطرفي</p>
                <div class="welcome-audio-options" data-welcome-options aria-label="اختيار أجواء الموقع" hidden>
                    <button class="welcome-audio-option" type="button" data-welcome-audio="islamic"><span class="welcome-option-number">١</span><span><strong>أصوات إسلامية</strong></span><span class="welcome-option-arrow" aria-hidden="true">←</span></button>
                    <button class="welcome-audio-option" type="button" data-welcome-audio="oud"><span class="welcome-option-number">٢</span><span><strong>موسيقى أدبية</strong></span><span class="welcome-option-arrow" aria-hidden="true">←</span></button>
                    <button class="welcome-audio-option" type="button" data-welcome-audio="darwish"><span class="welcome-option-number">٣</span><span><strong>محمود درويش</strong></span><span class="welcome-option-arrow" aria-hidden="true">←</span></button>
                    <button class="welcome-audio-option welcome-silent-option" type="button" data-welcome-audio="silent"><span class="welcome-option-number" aria-hidden="true">◌</span><span><strong>التصفح بدون صوت</strong></span><span class="welcome-option-arrow" aria-hidden="true">←</span></button>
                </div>
            </div>
            <div class="welcome-card-footer"><span data-welcome-hint aria-live="polite">يمكنك البدء الآن، أو اختيار أجواءك بعد ظهور الرسالة.</span><button class="button button-dark welcome-start" type="button" data-welcome-start>ابدأ التصفح</button></div>
        </article>
    </dialog>
    @stack('dialogs')
    @stack('scripts')
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
</body>
</html>
