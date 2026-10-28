@extends('layouts.app')
@section('title', 'استعادة كلمة المرور — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> استعادة الحساب</div>
        <h1>نسيت كلمة<br><em>المرور؟</em></h1>
        <p>أدخل البريد المرتبط بحسابك. سنرسل الرابط إذا كان البريد مسجلًا ونجح الإرسال.</p>
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('password.email') }}" class="account-form">
            @csrf
            <label class="field-label" for="email">البريد الإلكتروني<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" required autofocus>@error('email')<small class="field-error" id="email-error">{{ $message }}</small>@enderror</label>
            <button class="button button-dark" type="submit">إرسال تعليمات الاستعادة <span aria-hidden="true">←</span></button>
        </form>
        @if (config('mail.default') === 'log')<p class="mail-setup-note">البريد غير مربوط حاليًا؛ رسائل الاستعادة تُسجّل محليًا ولن تصل إلى صندوق البريد حتى إعداد خدمة الإرسال.</p>@endif
        <p class="auth-switch"><a href="{{ route('login') }}">العودة إلى تسجيل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>تظل الكلمات محفوظة، ونساعدك على استعادة طريقك إليها.</p><span>مِداد · استعادة الحساب</span></div>
</main>
@endsection
