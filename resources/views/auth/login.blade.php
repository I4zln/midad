@extends('layouts.app')
@section('title', 'تسجيل الدخول — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> أهلًا بعودتك</div>
        <h1>مساحتك <em>بين الكلمات.</em></h1>
        <p>سجّل الدخول لتنشر اقتباساتك وتحفظ ما يعني لك.</p>
        @if ($reason)<div class="form-alert auth-reason" role="note">{{ $reason }}</div>@endif
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('login.store') }}" class="account-form" data-validate>
            @csrf
            <x-form-field for="email" label="البريد الإلكتروني" help="البريد الذي أنشأت به حسابك.">
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" dir="ltr" required autofocus>
            </x-form-field>
            <x-form-field for="password" label="كلمة المرور" help="كلمة المرور التي اخترتها عند التسجيل.">
                <x-password-input id="password" autocomplete="current-password" />
            </x-form-field>
            <div class="auth-row">
                <label class="remember-option"><input type="checkbox" name="remember" @checked(old('remember'))> تذكرني على هذا الجهاز</label>
                <a class="forgot-password-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
            </div>
            <x-button class="auth-submit">تسجيل الدخول <span aria-hidden="true">←</span></x-button>
        </form>
        <p class="auth-switch">ليس لديك حساب؟ <a href="{{ route('register') }}">أنشئ حسابًا في أقل من دقيقة</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>بعض الكلمات لا نقرأها مرة واحدة؛ نعود إليها كلما احتجنا أن نتذكّر.</p><span>من مكتبة مِداد</span></div>
</main>
@endsection
