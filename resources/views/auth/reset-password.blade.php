@extends('layouts.app')
@section('title', 'تعيين كلمة مرور جديدة — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> خطوة أخيرة</div>
        <h1>اختر كلمة<br><em>مرور جديدة.</em></h1>
        <p>اختر كلمة مرور جديدة لا تقل عن 8 أحرف، ثم أكّدها.</p>
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('password.update') }}" class="account-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="field-label" for="email">البريد الإلكتروني<input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" required>@error('email')<small class="field-error" id="email-error">{{ $message }}</small>@enderror</label>
            <label class="field-label" for="password">كلمة المرور الجديدة<span class="password-field"><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" required><button class="password-visibility" type="button" data-toggle-password="password" aria-pressed="false">إظهار</button></span>@error('password')<small class="field-error" id="password-error">{{ $message }}</small>@enderror</label>
            <label class="field-label" for="password_confirmation">تأكيد كلمة المرور<span class="password-field"><input id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required><button class="password-visibility" type="button" data-toggle-password="password_confirmation" aria-pressed="false">إظهار</button></span></label>
            <button class="button button-dark" type="submit">حفظ كلمة المرور <span aria-hidden="true">←</span></button>
        </form>
        <p class="auth-switch"><a href="{{ route('login') }}">العودة إلى تسجيل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>كلمة جديدة، وبداية هادئة لعودتك إلى مكتبتك.</p><span>مِداد · استعادة الحساب</span></div>
</main>
@endsection
