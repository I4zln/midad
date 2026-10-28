@extends('layouts.app')
@section('title', 'دليل الشعراء والكتّاب — مِداد')
@section('content')
<main class="inner-page poets-directory">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>دليل الأسماء</span></nav>
    <header class="page-intro">
        <div class="eyebrow"><span class="eyebrow-line"></span> من قاعدة المكتبة</div>
        <h1>أصواتٌ <em>لها مكان.</em></h1>
        <p>أسماء الكتّاب هنا مأخوذة من الاقتباسات المحفوظة فعلًا في مِداد.</p>
    </header>
    <div class="poets-directory-list">
        @forelse ($poets as $index => $poet)
            <a class="poet-directory-row" href="{{ route('authors.show', ['author' => $poet->author]) }}">
                <span class="poet-directory-index">{{ sprintf('%02d', $index + 1) }}</span>
                <span class="poet-directory-name">{{ $poet->author }}</span>
                <span class="poet-directory-count">{{ $poet->quotes_count }} {{ $poet->quotes_count === 1 ? 'اقتباس محفوظ' : 'اقتباسًا محفوظًا' }}</span>
                <span class="poet-directory-arrow" aria-hidden="true">↗</span>
            </a>
        @empty
            <div class="empty-state"><span class="empty-mark">إ</span><h3>تبدأ الذاكرة باسم.</h3><p>لا توجد أسماء كتّاب مرتبطة باقتباسات بعد. أضف اقتباسًا موثق المصدر ليظهر صاحبه هنا.</p><a class="button button-dark" href="{{ route('home') }}#quotes">اذهب إلى المكتبة <span aria-hidden="true">←</span></a></div>
        @endforelse
    </div>
</main>
@endsection
