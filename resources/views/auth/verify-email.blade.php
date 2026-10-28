@extends('layouts.app')
@section('title', 'تأكيد البريد — مِداد')
@section('content')
<main class="inner-page auth-page">
    <section class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> خطوة أخيرة</div>
        <h1>أكّد بريدك الإلكتروني</h1>
        @if (config('mail.default') === 'log' || session('status') === 'verification-link-failed')
            <p>تعذر إرسال رابط التأكيد لأن خدمة البريد غير متصلة حاليًا. لن تظهر البطاقة الخاصة قبل تأكيد ملكية البريد.</p>
        @else
            <p>{{ session('status') === 'verification-link-sent' ? 'أرسلنا رابط تأكيد إلى بريدك. بعد فتحه ستظهر لك بطاقة جرير الخاصة.' : 'أكّد بريدك الإلكتروني للمتابعة إلى بطاقتك الخاصة.' }}</p>
        @endif
        @if (session('status') === 'verification-link-sent')<p class="status-message">أُرسل رابط التأكيد إلى بريدك.</p>@endif
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="button button-dark" type="submit">إعادة إرسال رابط التأكيد</button></form>
    </section>
</main>
@endsection
