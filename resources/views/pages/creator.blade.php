@extends('layouts.app')
@section('title', 'عنّي — عبدالعزيز المطرفي | مِداد')
@section('content')
<main class="inner-page creator-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">مِداد</a><span>←</span><span>عنّي</span></nav>
    <section class="creator-hero">
        <div class="creator-avatar" aria-label="الأحرف الأولى من اسم عبدالعزيز المطرفي">ع</div>
        <div class="creator-intro">
            <div class="eyebrow"><span class="eyebrow-line"></span> الشخص الذي يقف خلف مِداد</div>
            <h1>عبدالعزيز تركي <em>المطرفي</em></h1>
            <p>طالب هندسة برمجيات ومطوّر ويب · مكة المكرمة</p>
            <div class="creator-socials" aria-label="حساباتي">
                <a href="https://www.linkedin.com/in/abdulaziz-al-matrafi-bbb238358/" target="_blank" rel="noopener noreferrer"><strong>LinkedIn</strong><span>Abdulaziz Al-Matrafi ↗</span></a>
                <a href="https://github.com/I4zln" target="_blank" rel="noopener noreferrer"><strong>GitHub</strong><span>I4zln ↗</span></a>
                <a href="https://www.instagram.com/i1zln/" target="_blank" rel="noopener noreferrer"><strong>Instagram</strong><span>@i1zln ↗</span></a>
                <a href="https://x.com/i4zl4" target="_blank" rel="noopener noreferrer"><strong>X</strong><span>@i4zl4 ↗</span></a>
            </div>
        </div>
        <span class="creator-seal" aria-hidden="true">ع</span>
    </section>

    <section class="creator-about">
        <div><span class="eyebrow"><span class="eyebrow-line"></span> نبذة عنّي</span><h2>من الدعم التقني<br>إلى <em>البرمجيات.</em></h2></div>
        <div><p>مرحبًا، أنا عبدالعزيز. تخرجت بدبلوم علوم الحاسب والبرمجة، وأدرس الآن هندسة البرمجيات في جامعة أم القرى بمكة المكرمة.</p><p>أحب بناء أشياء يستفيد منها الناس فعلًا. تعلّمت من الدعم التقني أن وراء كل مشكلة شخصًا يحتاج حلًا واضحًا؛ ومن هنا بدأ اهتمامي بتطوير الويب وصناعة تجارب سهلة ومفيدة.</p><div class="creator-skills"><span>React.js</span><span>Laravel</span><span>PHP</span><span>SQL</span></div></div>
    </section>

    <section class="creator-work">
        <div class="creator-section-title"><span class="eyebrow"><span class="eyebrow-line"></span> ما أعمل عليه</span><h2>أبني حلولًا <em>تخدم الناس.</em></h2></div>
        <div class="creator-work-grid">
            <article><span class="creator-work-icon" aria-hidden="true">⌘</span><h3>تطوير الويب المتكامل</h3><p>بناء تطبيقات الويب من قاعدة البيانات حتى الواجهة، مع تركيز على الوضوح وسهولة الاستخدام.</p></article>
            <article><span class="creator-work-icon" aria-hidden="true">⌕</span><h3>حل المشكلات</h3><p>تحويل احتياج المستخدم إلى خطوات عملية، واختبار الحل حتى يعمل كما ينبغي.</p></article>
            <article><span class="creator-work-icon" aria-hidden="true">↗</span><h3>العمل الجماعي</h3><p>التعلّم مع الفريق، ومشاركة المعرفة، وإنجاز المشاريع بروح تعاونية.</p></article>
        </div>
        <div class="creator-current"><strong>حاليًا</strong><span>أطوّر مِداد، وأتعمق في هندسة البرمجيات وتصميم تجارب عربية أجمل.</span></div>
    </section>

    <section class="creator-education">
        <div class="creator-section-title"><span class="eyebrow"><span class="eyebrow-line"></span> رحلة التعلّم</span><h2>التعليم <em>والتجربة.</em></h2></div>
        <div class="creator-timeline">
            <article><span class="creator-timeline-date">٢٠٢٥ — ٢٠٢٩</span><div><h3>بكالوريوس هندسة البرمجيات</h3><p>جامعة أم القرى · مكة المكرمة</p><small>أدرس هندسة المتطلبات، وهياكل البيانات، والبرمجة كائنية التوجه وتصميم البرمجيات.</small></div></article>
            <article><span class="creator-timeline-date">٢٠٢٢ — ٢٠٢٥</span><div><h3>دبلوم علوم الحاسب والبرمجة</h3><p>جامعة أم القرى</p><small>أساس متين في علوم الحاسب والبرمجة والدعم التقني.</small></div></article>
        </div>
    </section>
    <section class="about-note"><span class="eyebrow"><span class="eyebrow-line"></span> مساحة منّي</span><p>مِداد مشروع أعمل عليه ليجمع بين التقنية والكلمة العربية.</p><a class="button button-dark" href="{{ route('home') }}">اكتشف مِداد <span aria-hidden="true">←</span></a></section>
</main>
@endsection
