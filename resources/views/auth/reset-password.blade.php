@extends('layouts.app')
@section('title', 'تعيين كلمة مرور جديدة — مِداد')
@section('content')
<main class="auth-page">
    <div class="auth-card">
        <div class="eyebrow"><span class="eyebrow-line"></span> خطوة أخيرة</div>
        <h1>اختر كلمة<br><em>مرور جديدة.</em></h1>
        <p>بعد الحفظ تدخل إلى حسابك مباشرة.</p>
        @if (session('status'))<div class="form-alert auth-status" role="status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('password.update') }}" class="account-form" data-validate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form-field for="email" label="البريد الإلكتروني" help="البريد الذي وصلك عليه رابط الاستعادة.">
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" dir="ltr" required>
            </x-form-field>
            <x-form-field for="password" label="كلمة المرور الجديدة" help="8 أحرف على الأقل، فيها حروف وأرقام.">
                <x-password-input id="password" autocomplete="new-password" minlength="8" autofocus />
            </x-form-field>
            <x-form-field for="password_confirmation" label="تأكيد كلمة المرور" help="أعد كتابة كلمة المرور الجديدة.">
                <x-password-input id="password_confirmation" autocomplete="new-password" minlength="8" data-match="password" />
            </x-form-field>
            <x-button class="auth-submit">حفظ كلمة المرور <span aria-hidden="true">←</span></x-button>
        </form>
        <p class="auth-switch"><a href="{{ route('login') }}">العودة إلى تسجيل الدخول</a></p>
    </div>
    <div class="auth-aside"><span class="quote-mark">“</span><p>كلمة جديدة، وبداية هادئة لعودتك إلى مكتبتك.</p><span>مِداد · استعادة الحساب</span></div>
</main>
@endsection
