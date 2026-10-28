@extends('layouts.app')
@section('title', 'عنّي — عبدالعزيز المطرفي | مِداد')
@section('content')
@php
    $links = [
        ['LinkedIn', 'Abdulaziz Al-Matrafi', 'https://www.linkedin.com/in/abdulaziz-al-matrafi-bbb238358/'],
        ['GitHub', 'I4zln', 'https://github.com/I4zln'],
        ['Instagram', '@i1zln', 'https://www.instagram.com/i1zln/'],
        ['X', '@i4zl4', 'https://x.com/i4zl4'],
    ];
    $features = [
        ['تصفّح', 'اقرأ اقتباسات القرّاء من الأحدث، وابحث بكلمة أو صنّفها حسب المعنى.', route('quotes.index'), 'إلى الاقتباسات'],
        ['انشر', 'أنشئ حسابًا وأضف اقتباسًا من 3 إلى 155 حرفًا، يظهر باسمك وتاريخ نشره.', auth()->check() ? route('quotes.create') : route('register'), auth()->check() ? 'أضف اقتباسًا' : 'أنشئ حسابًا'],
        ['عدّل واحذف', 'اقتباساتك ملكك: تعدّلها أو تحذفها متى شئت، ولا يستطيع غيرك ذلك.', auth()->check() ? route('profile.show', auth()->user()) : route('login'), 'اقتباساتي'],
        ['أعجب واحفظ', 'أعجب بما يلامسك، واحفظ ما تريد العودة إليه في «المحفوظات».', auth()->check() ? route('favorites') : route('login'), 'المحفوظات'],
    ];
@endphp
<main class="inner-page midad-about">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>عنّي</span></nav>

    <header class="midad-about-intro">
        <span class="eyebrow"><span class="eyebrow-line"></span> عن مِداد</span>
        <h1>المِداد <em>حبرُ الكلمة.</em></h1>
        <p class="midad-about-lead">«المِداد» في العربية هو الحبر الذي تُكتب به الكلمات. ومن هنا جاء الاسم: مكتبة عربية صغيرة نحفظ فيها الاقتباسات التي تركت فينا أثرًا، وننسبها إلى أصحابها، ونشاركها مع من يقرأ على مهل.</p>
    </header>

    <section class="midad-about-features" aria-labelledby="midad-about-features-title">
        <h2 id="midad-about-features-title">ماذا يمكنك أن تفعل هنا؟</h2>
        <ol>
            @foreach ($features as $index => [$title, $text, $href, $cta])
                <li>
                    <span class="midad-about-number" aria-hidden="true">{{ ['١', '٢', '٣', '٤'][$index] }}</span>
                    <div><h3>{{ $title }}</h3><p>{{ $text }}</p><a href="{{ $href }}">{{ $cta }} <span aria-hidden="true">←</span></a></div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="midad-about-owner" aria-labelledby="midad-about-owner-title">
        <span class="midad-about-initial" aria-hidden="true">ع</span>
        <div>
            <span class="eyebrow"><span class="eyebrow-line"></span> صانع مِداد</span>
            <h2 id="midad-about-owner-title">عبدالعزيز تركي المطرفي</h2>
            <p>صمّمت مِداد وبنيتها مشروعًا نهائيًا في معسكر القمّة بنادي طويق، لتجمع بين التقنية وجمال الكلمة العربية.</p>
            <ul class="midad-about-links" aria-label="حساباتي">
                @foreach ($links as [$network, $handle, $url])
                    <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer"><strong>{{ $network }}</strong><span dir="ltr">{{ $handle }}</span><span class="sr-only">(يفتح في نافذة جديدة)</span></a></li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="midad-about-close">
        <p>ابدأ من كلمة؛ قد تجد اقتباسًا يشبهك، أو تترك واحدًا يبقى مع قارئ آخر.</p>
        <x-button href="{{ route('quotes.index') }}">اكتشف الاقتباسات <span aria-hidden="true">←</span></x-button>
    </section>
</main>
@endsection
