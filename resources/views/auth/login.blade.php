@extends('layouts.app')
@section('title', 'تسجيل الدخول — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> أهلًا بعودتك</div>
        <h1>مساحتك <em>بين الكلمات.</em></h1>
        <p>سجّل الدخول لتتابع الكتّاب وتحفظ الاقتباسات التي تعني لك.</p>
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('login.store') }}" class="account-form">
            @csrf
            <label class="field-label" for="identifier">البريد الإلكتروني أو اسم المستخدم <small class="composer-field-hint">أدخل البريد الذي سجّلت به.</small><input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" autocomplete="username" required autofocus></label>
            <label class="field-label" for="password">كلمة المرور <small class="composer-field-hint">أدخل كلمة مرور حسابك.</small>
                <span class="password-field"><input id="password" name="password" type="password" autocomplete="current-password" required><button class="password-visibility" type="button" data-toggle-password="password" aria-pressed="false">إظهار</button></span>
            </label>
            <a class="forgot-password-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
            <label class="remember-option"><input type="checkbox" name="remember"> تذكرني على هذا الجهاز</label>
            <button class="button button-dark" type="submit">تسجيل الدخول <span aria-hidden="true">←</span></button>
        </form>
        <p class="auth-switch">ليس لديك حساب؟ <a href="{{ route('register') }}">أنشئ حسابًا جديدًا</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>بعض الكلمات لا نقرأها مرة واحدة؛ نعود إليها كلما احتجنا أن نتذكّر.</p><span>من مكتبة أثر</span></div>
</main>
@endsection
