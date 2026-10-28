@extends('mail.layout')
@section('title', 'مِداد | طلب إعادة تعيين كلمة المرور')
@section('preheader', 'رابط آمن لاختيار كلمة مرور جديدة لحسابك في مِداد.')
@section('eyebrow', 'استعادة الحساب')
@section('heading', 'إعادة تعيين كلمة المرور')
@section('content')
    <p class="m-soft" style="margin:0 0 26px;color:#5f5c55;font-size:16px;line-height:1.95">تلقّينا طلبًا لإعادة تعيين كلمة المرور لحسابك في مِداد. اضغط الزر لاختيار كلمة مرور جديدة، وستدخل حسابك مباشرة بعد حفظها.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 26px"><tr>
        @include('mail.partials.button', ['url' => $url, 'label' => 'اختيار كلمة مرور جديدة'])
    </tr></table>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td class="m-note" style="padding:14px 16px;border:1px solid #ece6da;border-radius:12px;background:#f5f2eb;color:#5f5c55;font-size:13px;line-height:1.9">
            صلاحية الرابط {{ $expiresInMinutes }} دقيقة، ويُستخدم مرة واحدة.<br>
            إن لم تطلب ذلك فتجاهل هذه الرسالة، ولن تتغيّر كلمة المرور.
        </td>
    </tr></table>
    @include('mail.partials.link-fallback', ['url' => $url])
@endsection
@section('footer', 'وصلتك هذه الرسالة لأن أحدًا طلب استعادة حساب مرتبط بهذا البريد في مِداد.')
