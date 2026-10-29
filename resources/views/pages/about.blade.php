@extends('layouts.app')
@section('title', 'من نحن | مِداد')
@section('content')
@php
    $signedIn = auth()->check();
    $features = [
        ['تصفّح وابحث', 'اقرأ أحدث الاقتباسات، وابحث بكلمة أو باسم كاتب، وصفِّها حسب التصنيف.', route('quotes.index'), 'إلى الاقتباسات'],
        ['انشر اقتباسك', 'أنشئ حسابًا وأضف اقتباسًا من 3 إلى 155 حرفًا، يظهر باسمك.', $signedIn ? route('quotes.create') : route('register'), $signedIn ? 'أضف اقتباسًا' : 'أنشئ حسابًا'],
        ['عدّل واحذف ما نشرت', 'اقتباساتك لك وحدك؛ تعدّلها أو تحذفها متى شئت.', $signedIn ? route('profile.show', auth()->user()) : route('login'), 'اقتباساتي'],
        ['أعجب واحفظ', 'أعجب بما يلامسك، واحفظ ما تريد العودة إليه في «المحفوظات».', $signedIn ? route('favorites') : route('login'), 'المحفوظات'],
    ];
    $skills = [
        ['React.js', 'أحب بناء واجهات تطبيقات الويب المتكاملة به إلى جانب Laravel.'],
        ['Laravel', 'بنيت به مِداد: الحسابات، ونشر الاقتباسات، والإعجاب والحفظ.'],
        ['PHP', 'لغة مِداد، ونظام إدارة طلبات الورشة في تدريب وزارة التعليم.'],
        ['SQL', 'قاعدة بيانات مِداد، وقاعدة MySQL لنظام الورشة.'],
    ];
    $journey = [
        ['2022–2025', 'دبلوم البرمجة وعلوم الحاسب', 'جامعة أم القرى.'],
        ['2024', 'تدريب في وزارة التعليم بمكة', 'بنيت نظام ويب لإدارة طلبات الورشة بـ PHP وMySQL، وحصلت على شهادة تقدير.'],
        ['2024–2025', 'تدريب تعاوني في أمانة العاصمة المقدسة', 'دعم الشبكات والأنظمة والأمن السيبراني.'],
        ['من 2025', 'بكالوريوس هندسة البرمجيات', 'جامعة أم القرى، وعضو في نادي طويق.'],
    ];
    $links = [
        ['linkedin', 'LinkedIn', 'Abdulaziz Al-Matrafi', 'https://www.linkedin.com/in/abdulaziz-al-matrafi-bbb238358/'],
        ['github', 'GitHub', 'I4zln', 'https://github.com/I4zln'],
        ['instagram', 'Instagram', '@i1zln', 'https://www.instagram.com/i1zln/'],
        ['x', 'X', '@i4zl4', 'https://x.com/i4zl4'],
    ];
@endphp
<main class="inner-page us-page" data-about-page>
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>من نحن</span></nav>

    <header class="us-hero">
        <span class="eyebrow"><span class="eyebrow-line"></span> من نحن</span>
        <h1 class="us-hero-title">مِداد</h1>
        <p class="us-hero-meaning">المِداد هو الحبر الذي تُكتب به الكلمات.</p>
        <p class="us-hero-slogan">«لأن للكلمات أثرًا يبقى»</p>
    </header>

    <x-ink-divider />

    <section class="us-section us-what" aria-labelledby="us-what-title" data-reveal>
        <h2 id="us-what-title">ما هي مِداد؟</h2>
        <p>مِداد مساحة عربية هادئة لمشاركة الاقتباسات والشعر. يتصفّحها الجميع، وينشر فيها المسجّلون ما ترك فيهم أثرًا مع نسبته إلى قائله. أردناها مكتبة صغيرة تُقرأ على مهل.</p>
    </section>

    <x-ink-divider />

    <section class="us-section" aria-labelledby="us-features-title" data-reveal>
        <h2 id="us-features-title">ماذا يمكنك أن تفعل هنا؟</h2>
        <ol class="us-features">
            @foreach ($features as $index => [$title, $text, $href, $cta])
                <li>
                    <span class="us-feature-number" aria-hidden="true">{{ ['١', '٢', '٣', '٤'][$index] }}</span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $text }}</p>
                    <a href="{{ $href }}">{{ $cta }} <span aria-hidden="true">←</span></a>
                </li>
            @endforeach
        </ol>
    </section>

    <x-ink-divider />

    <section class="us-section us-maker" id="maker" aria-labelledby="us-maker-title" data-reveal>
        <div class="us-maker-head">
            <x-ink-seal class="us-seal" />
            <div>
                <span class="eyebrow"><span class="eyebrow-line"></span> صانع مِداد</span>
                <h2 id="us-maker-title">عبدالعزيز تركي المطرفي</h2>
            </div>
        </div>
        <p class="us-maker-bio">طالب هندسة برمجيات في جامعة أم القرى، أحب بناء تطبيقات الويب المتكاملة بـ Laravel وReact. صمّمت مِداد وبنيتها مشروعًا نهائيًا في معسكر القمة بنادي طويق.</p>

        <div class="us-block">
            <h3 id="us-skills-title">المهارات</h3>
            <ul class="us-skills" aria-labelledby="us-skills-title">
                @foreach ($skills as $index => [$skill, $note])
                    <li><button type="button" class="us-skill" aria-pressed="false" aria-describedby="us-skill-tip-{{ $index }}" data-skill-note="us-skill-tip-{{ $index }}" dir="ltr">{{ $skill }}</button><span id="us-skill-tip-{{ $index }}" hidden>{{ $note }}</span></li>
                @endforeach
            </ul>
            <p class="us-skill-note" data-skill-output aria-live="polite" data-default="مرّر المؤشر على مهارة أو اضغطها لترى ما بنيته بها.">مرّر المؤشر على مهارة أو اضغطها لترى ما بنيته بها.</p>
        </div>

        <div class="us-block">
            <h3>الرحلة</h3>
            <ol class="us-journey" data-journey>
                @foreach ($journey as [$year, $title, $detail])
                    <li class="us-journey-item">
                        <details>
                            <summary><span class="us-journey-dot" aria-hidden="true"></span><span class="us-journey-year" dir="auto">{{ $year }}</span><span class="us-journey-title">{{ $title }}</span></summary>
                            <p>{{ $detail }}</p>
                        </details>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="us-block us-side-project">
            <h3>مشروع جانبي</h3>
            <p>متجر قطع حاسب مع أداة <span dir="ltr">PC Builder</span> لفحص التوافق.</p>
        </div>

        <div class="us-block">
            <h3 id="us-links-title">تواصل معي</h3>
            <ul class="us-links" aria-labelledby="us-links-title">
                @foreach ($links as [$key, $network, $handle, $url])
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                            <x-social-icon :network="$key" />
                            <span class="us-link-text"><strong>{{ $network }}</strong><span dir="ltr">{{ $handle }}</span></span>
                            <svg class="us-link-stroke" viewBox="0 0 100 8" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path pathLength="1" d="M98 4 C70 1 48 7 26 4 S6 3 2 5" /></svg>
                            <span class="sr-only">(يفتح في نافذة جديدة)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <x-ink-divider />

    <section class="us-close" data-reveal>
        <p>ابدأ من كلمة؛ قد تترك اقتباسًا يبقى مع قارئ آخر.</p>
        <x-button :href="$signedIn ? route('quotes.create') : route('login', ['reason' => 'add', 'next' => '/quotes/create'])">＋ أضف اقتباسًا <x-fountain-pen class="pen-icon" /></x-button>
    </section>
</main>
@endsection
