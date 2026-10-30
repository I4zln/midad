@extends('layouts.app')
@section('title', 'استعادة كلمة المرور — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> استعادة الحساب</div>
        <h1>نسيت كلمة<br><em>المرور؟</em></h1>
        <p>أدخل بريدك، وسنرسل إليه رابطًا آمنًا لاختيار كلمة مرور جديدة.</p>
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
        @php($whatsapp = config('ethraa.admin_whatsapp'))
        @php($helpText = \App\Http\Controllers\PasswordResetController::helpMessage())
        @php($helpTemplate = \App\Http\Controllers\PasswordResetController::helpMessage('__EMAIL__'))
        {{-- WhatsApp opens with the message written (and the email typed above, added by app.js); the member only taps Send. --}}
        <p class="auth-switch">لم تصلك الرسالة؟ @if ($whatsapp)<a href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode($helpText) }}" target="_blank" rel="noopener noreferrer" data-whatsapp-help="{{ $whatsapp }}" data-help-text="{{ $helpText }}" data-help-template="{{ $helpTemplate }}">تواصل مع إدارة مِداد على واتساب</a>@else<a href="{{ route('creator') }}">تواصل مع إدارة مِداد</a>@endif، وسنرسل لك رابط الاستعادة بأنفسنا. راسلنا من رقم الاستعادة إن كنت أضفته في ملفك، فهو ما نتحقق به أنك صاحب الحساب.</p>
        <p class="auth-switch">تذكّرتها؟ <a href="{{ route('login') }}">العودة إلى تسجيل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>تظل الكلمات محفوظة، ونساعدك على استعادة طريقك إليها.</p><span>مِداد · استعادة الحساب</span></div>
</main>
@endsection
