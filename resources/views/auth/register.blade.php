@extends('layouts.app')
@section('title', 'إنشاء حساب — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> ابدأ حكايتك</div>
        <h1>مكانٌ لك <em>بين الكلمات.</em></h1>
        <p>أربعة حقول فقط، ثم تشارك اقتباسك الأول.</p>
        @if ($reason)<div class="form-alert auth-reason" role="note">{{ $reason }}</div>@endif
        <form method="POST" action="{{ route('register.store') }}" class="account-form" data-validate>
            @csrf
            <x-form-field for="name" label="الاسم" help="من 2 إلى 50 حرفًا، وسيظهر مع اقتباساتك.">
                <input id="name" name="name" value="{{ old('name') }}" minlength="2" maxlength="50" autocomplete="name" required autofocus>
            </x-form-field>
            <x-form-field for="email" label="البريد الإلكتروني" help="بريد صحيح لم يُستخدم في حساب آخر.">
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" dir="ltr" required>
            </x-form-field>
            <x-form-field for="password" label="كلمة المرور" help="8 أحرف على الأقل.">
                <x-password-input id="password" autocomplete="new-password" minlength="8" />
            </x-form-field>
            <x-form-field for="password_confirmation" label="تأكيد كلمة المرور" help="أعد كتابة كلمة المرور نفسها.">
                <x-password-input id="password_confirmation" autocomplete="new-password" minlength="8" data-match="password" />
            </x-form-field>
            <x-button class="auth-submit">إنشاء الحساب <span aria-hidden="true">←</span></x-button>
        </form>
        <p class="auth-switch">لديك حساب؟ <a href="{{ route('login') }}">سجّل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>كل مكتبة تبدأ بكلمة، وكل كلمة تجد طريقها إلى قارئها.</p><span>مِداد | MIDAD</span></div>
</main>
@endsection
