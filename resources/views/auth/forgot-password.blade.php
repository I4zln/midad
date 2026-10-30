@extends('layouts.app')
@section('title', 'استعادة كلمة المرور — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> استعادة الحساب</div>
        <h1>نسيت كلمة<br><em>المرور؟</em></h1>
        <p>{{ $mailWorks ? 'أدخل بريدك، وسنرسل إليه رابطًا آمنًا لاختيار كلمة مرور جديدة.' : 'أدخل بريدك، وسنساعدك على اختيار كلمة مرور جديدة.' }}</p>
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        @if (session('whatsapp'))
            {{-- After the request: app.js opens this chat by itself; the button is there if the browser does not follow. --}}
            <div class="auth-whatsapp" data-whatsapp-open="{{ session('whatsapp') }}">
                <p>جارٍ فتح واتساب لتُرسل طلبك إلى الإدارة، والرسالة مكتوبة لك ببريدك…</p>
                <a class="button button-dark" href="{{ session('whatsapp') }}" target="_blank" rel="noopener noreferrer">افتح واتساب</a>
            </div>
        @endif
        <form method="POST" action="{{ route('password.email') }}" class="account-form" data-validate>
            @csrf
            <x-form-field for="email" label="البريد الإلكتروني" help="البريد الذي أنشأت به حسابك.">
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" dir="ltr" required autofocus>
            </x-form-field>
            <x-button class="auth-submit">أرسل رابط الاستعادة <span aria-hidden="true">←</span></x-button>
        </form>
        @unless ($mailWorks)
            {{-- Only while this host cannot send email (MAIL_MAILER=log on Render's free plan); the button above then continues on WhatsApp. --}}
            <p class="auth-note">نعتذر، إرسال الرسائل إلى البريد متوقف مؤقتًا على خادم مِداد. أدخل بريدك واضغط الزر، وسيفتح لك واتساب لنرسل لك رابط الاستعادة بأنفسنا.</p>
        @endunless
        <p class="auth-switch">تذكّرتها؟ <a href="{{ route('login') }}">العودة إلى تسجيل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>تظل الكلمات محفوظة، ونساعدك على استعادة طريقك إليها.</p><span>مِداد · استعادة الحساب</span></div>
</main>
@endsection
