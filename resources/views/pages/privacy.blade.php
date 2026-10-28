@extends('layouts.app')
@section('title', 'الخصوصية | مِداد')
@section('content')
{{-- Plain description of what the site actually stores and why; keep it in step with the code. --}}
<main class="inner-page us-page privacy-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>الخصوصية</span></nav>

    <header class="us-section">
        <span class="eyebrow"><span class="eyebrow-line"></span> الخصوصية</span>
        <h1 class="privacy-title">ماذا نحفظ عنك، ولماذا</h1>
        <p class="privacy-lead">نحفظ أقل ما يلزم لتعمل مِداد: حسابك، وما تنشره، وما تختاره بنفسك. لا نبيع بياناتك، ولا نضع إعلانات أو أدوات تتبّع.</p>
    </header>

    <section class="us-section" aria-labelledby="privacy-collect">
        <h2 id="privacy-collect">ما نحفظه</h2>
        <ul class="privacy-list">
            <li><strong>حسابك:</strong> الاسم والبريد الإلكتروني وكلمة المرور. تُحفظ كلمة المرور مشفّرة تشفيرًا لا يمكن عكسه، فلا يراها أحد، ولا نحن.</li>
            <li><strong>ما تضيفه بنفسك:</strong> النبذة والمدينة والتعليم والمهارات وروابط حساباتك والصورة الشخصية والغلاف، إن اخترت إضافتها.</li>
            <li><strong>نشاطك في المكتبة:</strong> اقتباساتك وتعليقاتك وإعجاباتك ومحفوظاتك ومن تتابع.</li>
            <li><strong>جلسة الدخول:</strong> ملف تعريف ارتباط (كوكي) واحد يبقيك مسجّل الدخول، ومعه عنوان IP ونوع المتصفح لحماية الحساب. تنتهي الجلسة بعد ساعتين بلا نشاط.</li>
            <li><strong>تفضيلات على جهازك فقط:</strong> اختيار المظهر والصوت يُحفظ في متصفحك ولا يصل إلينا.</li>
            <li><strong>«اكتشف»:</strong> يُرتَّب لك مما تخزّنه مِداد أصلًا، أي إعجاباتك ومحفوظاتك وتصنيفاتها، دون أي تتبّع خارج الموقع. نتذكّر خلال الجلسة أرقام الاقتباسات التي عُرضت عليك كي لا تتكرر، وتُمحى بانتهاء الجلسة. وإن كنت مسجّلًا نحفظ طريقة العرض التي اخترتها («تأمّل» أو «الخط الزمني») في حسابك.</li>
        </ul>
    </section>

    <section class="us-section" aria-labelledby="privacy-who">
        <h2 id="privacy-who">من يرى ماذا</h2>
        <ul class="privacy-list">
            <li><strong>للجميع:</strong> اسمك وما تضيفه إلى ملفك، واقتباساتك وتعليقاتك.</li>
            <li><strong>لك وحدك:</strong> بريدك الإلكتروني، وقوائم «المعجب بها» و«المحفوظات»، وإشعاراتك. لا يظهر بريدك لأي مستخدم آخر.</li>
            <li><strong>البريد المرسل منا:</strong> رسائل الترحيب واستعادة كلمة المرور وتأكيد البريد تُرسل عبر خدمة Gmail من Google، فتمرّ عبرها عناوين المستلمين ومحتوى تلك الرسائل.</li>
            <li><strong>الاتصال بالموقع:</strong> يصل الموقع إليك عبر Cloudflare، فيمرّ الاتصال المشفّر عبر خوادمها.</li>
        </ul>
    </section>

    <section class="us-section" aria-labelledby="privacy-control">
        <h2 id="privacy-control">تحكّمك في بياناتك</h2>
        <ul class="privacy-list">
            <li>تعدّل ملفك أو تحذف أي اقتباس نشرته متى شئت.</li>
            <li>تحذف حسابك وكل بياناتك نهائيًا من «تعديل الملف» ← «حذف الحساب».</li>
        </ul>
    </section>
</main>
@endsection
