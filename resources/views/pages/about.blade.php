@extends('layouts.app')
@section('title', 'عن مِداد | MIDAD')
@section('content')
<main class="inner-page about-midad">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>عن مِداد</span></nav>
    <section class="about-hero">
        <div class="eyebrow"><span class="eyebrow-line"></span> مكتبة عربية حيّة</div>
        <h1>الكلمة الجميلة<br><em>تستحق أن تُحفظ.</em></h1>
        <p>مِداد | MIDAD منصة عربية تفاعلية لنشر الاقتباسات والشعر وإدارتها ومشاركتها. يتصفح الزوار الاقتباسات المنشورة، وينشر المستخدمون المسجلون اقتباساتهم الخاصة ويعدّلونها ويتفاعلون مع غيرها بالإعجاب والحفظ والبحث والتصنيف.</p>
        <a class="button button-dark" href="{{ route('home') }}#quotes">اكتشف الاقتباسات <span aria-hidden="true">←</span></a>
        <span class="about-hero-mark" aria-hidden="true">«</span>
    </section>

    <section class="about-manifesto about-purpose">
        <div><span class="eyebrow"><span class="eyebrow-line"></span> لماذا مِداد؟</span><h2>لأن بعض الجمل<br>ترافقنا طويلًا.</h2></div>
        <p>بدأت مِداد من حبّ الكلمات العربية والرغبة في جمع الاقتباسات في مكان واحد مرتب وسهل. هنا تلتقي أبيات الشعر، والحِكم، والجمل التي نعود إليها؛ مع نسبة واضحة إلى الكاتب أو الشاعر ومصدر الاقتباس متى توفر.</p>
    </section>

    <section class="about-steps" aria-labelledby="about-steps-title">
        <header><div class="eyebrow"><span class="eyebrow-line"></span> مساحة تشاركية</div><h2 id="about-steps-title">اقرأ. احفظ. <em>أضف أثرَك.</em></h2></header>
        <div class="about-step-grid">
            <article><span>٠١</span><h3>اكتشف</h3><p>تصفّح اقتباسات عربية، وابحث عن العبارة أو الكاتب الذي تريد.</p></article>
            <article><span>٠٢</span><h3>احتفظ بما تحب</h3><p>أنشئ حسابًا لتضيف الاقتباسات إلى مفضلتك وتتابع الكتّاب.</p></article>
            <article><span>٠٣</span><h3>شارك اقتباسًا</h3><p>أضف كلماتك المفضلة مع اسم صاحبها ومصدرها، ثم تفاعل مع اختيارات القرّاء.</p></article>
        </div>
    </section>

    <section class="about-values">
        <div><span class="eyebrow"><span class="eyebrow-line"></span> ما نؤمن به</span><h2>نحفظ الكلمات<br><em>ونحترم أصحابها.</em></h2></div>
        <ul><li><strong>النسبة أولًا</strong><span>نحرص على ذكر الكاتب والمصدر، ولا ننسب قولًا بلا تحقق.</span></li><li><strong>المعنى قبل العدد</strong><span>كل اقتباس مساحة للتأمل والحوار، لا مجرد منشور عابر.</span></li><li><strong>الأدب يجمعنا</strong><span>مجتمع يكتشف أصوات الشعراء والكتّاب ويشارك ما يحب.</span></li></ul>
    </section>

    <section class="about-note"><span class="eyebrow"><span class="eyebrow-line"></span> ابدأ من كلمة</span><p>قد تجد هنا اقتباسًا يشبهك، أو تضيف اقتباسًا يبقى مع قارئ آخر.</p><a class="button button-dark" href="{{ route('home') }}#quotes">إلى مكتبة الاقتباسات <span aria-hidden="true">←</span></a></section>
</main>
@endsection
