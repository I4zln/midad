@extends('layouts.app')
@section('title', 'إنشاء حساب — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> ابدأ حكايتك</div>
        <h1>مكانٌ لك <em>بين الكلمات.</em></h1>
        <p>أنشئ حسابًا لتشارك اقتباساتك وتتابع ما يترك أثرًا فيك.</p>
        <form method="POST" action="{{ route('register.store') }}" class="account-form">
            @csrf
            <x-form-field for="name" label="الاسم" help="من 2 إلى 50 حرفًا."><input id="name" name="name" value="{{ old('name') }}" minlength="2" maxlength="50" autocomplete="name" required autofocus></x-form-field>
            <x-form-field for="email" label="البريد الإلكتروني" help="أدخل بريدًا صحيحًا غير مستخدم من قبل."><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></x-form-field>
            <label class="field-label" for="password">كلمة المرور <small class="composer-field-hint">8 أحرف على الأقل.</small>
                <span class="password-field"><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required><button class="password-visibility" type="button" data-toggle-password="password" aria-pressed="false">إظهار</button></span>
            </label>
            <label class="field-label" for="password_confirmation">تأكيد كلمة المرور <small class="composer-field-hint">أعد إدخال كلمة المرور نفسها.</small>
                <span class="password-field"><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required><button class="password-visibility" type="button" data-toggle-password="password_confirmation" aria-pressed="false">إظهار</button></span>
            </label>
            <button class="button button-dark" type="submit">إنشاء الحساب <span aria-hidden="true">←</span></button>
        </form>
        <p class="auth-switch">لديك حساب؟ <a href="{{ route('login') }}">سجّل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>كل مكتبة تبدأ بكلمة، وكل كلمة تجد طريقها إلى قارئها.</p><span>مِداد | MIDAD</span></div>
</main>
@endsection
