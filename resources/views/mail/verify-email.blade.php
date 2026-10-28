@extends('mail.layout')
@section('title', 'مِداد | تأكيد البريد الإلكتروني')
@section('preheader', 'خطوة واحدة لتأكيد أن هذا البريد لك.')
@section('eyebrow', 'تأكيد البريد')
@section('heading', 'أكّد بريدك الإلكتروني')
@section('content')
    <p class="m-soft" style="margin:0 0 26px;color:#5f5c55;font-size:16px;line-height:1.95">أهلًا {{ $name }}، اضغط الزر لتأكيد أن هذا البريد لك. بعد التأكيد يظهر في ملفك ما ينتظرك.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 26px"><tr>
        @include('mail.partials.button', ['url' => $url, 'label' => 'تأكيد البريد'])
    </tr></table>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td class="m-note" style="padding:14px 16px;border:1px solid #ece6da;border-radius:12px;background:#f5f2eb;color:#5f5c55;font-size:13px;line-height:1.9">
            صلاحية الرابط {{ $expiresInMinutes }} دقيقة. إن لم تنشئ حسابًا في مِداد فتجاهل هذه الرسالة.
        </td>
    </tr></table>
    @include('mail.partials.link-fallback', ['url' => $url])
@endsection
@section('footer', 'وصلتك هذه الرسالة لتأكيد بريد مرتبط بحساب في مِداد.')
