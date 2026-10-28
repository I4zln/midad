@extends('mail.layout')
@section('title', 'أهلًا بك في مِداد')
@section('preheader', 'أهلًا بك في مِداد — لأن للكلمات أثرًا يبقى.')
@section('eyebrow', 'عضوية جديدة')
@section('heading')أهلًا بك يا {{ $name }}@endsection
@section('content')
    <p class="m-soft" style="margin:0 0 24px;color:#5f5c55;font-size:16px;line-height:1.95">يسعدنا انضمامك إلى مِداد، المكتبة العربية التي نحفظ فيها الكلمات التي تترك فينا أثرًا. تصفّح ما شاركه القرّاء، واحفظ ما يلامسك، وشارك اقتباسك الأول حين تجد الكلمة التي تشبهك.</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px"><tr>
        <td class="m-verse" style="padding:20px 18px;border:1px solid #e4d5c3;border-right:3px solid #8f5c39;border-radius:12px;background:#f5f2eb;color:#24231f;font-family:'Traditional Arabic','Times New Roman',Georgia,serif;font-size:22px;line-height:2;text-align:center">«أهلًا بمَن جاءَ الحروفَ مُحِبًّا … فالحرفُ بيتٌ والمِدادُ رِحابُ»</td>
    </tr></table>
    <table role="presentation" class="m-buttons" cellpadding="0" cellspacing="0"><tr>
        @include('mail.partials.button', ['url' => $browseUrl, 'label' => 'تصفّح الاقتباسات', 'stack' => true])
        <td class="m-gap" style="width:10px;font-size:0">&nbsp;</td>
        @include('mail.partials.button', ['url' => $shareUrl, 'label' => '+ شارك أول اقتباس لك', 'variant' => 'secondary', 'stack' => true])
    </tr></table>
@endsection
@section('footer', 'وصلتك هذه الرسالة لأنك أنشأت حسابًا في مِداد بهذا البريد.')
