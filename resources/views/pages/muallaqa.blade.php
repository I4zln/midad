@extends('layouts.app')
@section('title', $muallaqa['poet'].' — المعلّقات السبع | مِداد')
@section('content')
<main class="inner-page muallaqat-page muallaqa-reading-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">مِداد</a><span>←</span><a href="{{ route('muallaqat.index') }}">المعلّقات السبع</a><span>←</span><span>{{ $muallaqa['poet'] }}</span></nav>
    <article class="muallaqa-reading">
        <figure class="muallaqa-reading-art"><img src="{{ str_starts_with($muallaqa['image'], 'http') ? $muallaqa['image'] : asset($muallaqa['image']) }}" alt="{{ $muallaqa['image_note'] }}" onerror="this.onerror=null;this.src='{{ asset('images/poets/literary-scribe.jpg') }}'"><figcaption>مرجع الصورة: <a href="{{ str_starts_with($muallaqa['image_source'], 'http') ? $muallaqa['image_source'] : asset($muallaqa['image_source']) }}" target="_blank" rel="noopener noreferrer">{{ $muallaqa['image_note'] }}</a></figcaption></figure>
        <div class="muallaqa-reading-copy"><div class="eyebrow"><span class="eyebrow-line"></span> من المعلّقات السبع</div><p class="muallaqa-reading-poet">{{ $muallaqa['poet'] }}</p><h1>{{ $muallaqa['title'] }}</h1><p class="muallaqa-reading-tagline">{{ $muallaqa['tagline'] }}</p><div class="muallaqa-reading-rule"></div><h2>عن الشاعر والقصيدة</h2><p>{{ $muallaqa['bio'] }}</p><p class="muallaqa-source-caveat">تختلف روايات القصيدة وبعض تفاصيل نسبتها في المصادر التراثية. لذلك لا نعرض هنا نصًا مبتورًا على أنه كامل.</p><div class="muallaqa-reference-links"><a class="button button-dark" href="{{ $muallaqa['poem_source'] }}" target="_blank" rel="noopener noreferrer">اقرأ النص في مصدره <span aria-hidden="true">↗</span></a></div></div>
    </article>
    <nav class="muallaqa-pagination" aria-label="التنقل بين المعلقات"><a href="{{ route('muallaqat.show', $previous['slug']) }}"><small>السابقة</small><strong>{{ $previous['poet'] }}</strong></a><a class="muallaqa-back" href="{{ route('muallaqat.index') }}">كل المعلّقات</a><a href="{{ route('muallaqat.show', $next['slug']) }}"><small>التالية</small><strong>{{ $next['poet'] }}</strong></a></nav>
</main>
@endsection