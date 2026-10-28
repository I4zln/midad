@extends('layouts.app')
@section('title', 'اقتباسات ' . $author . ' — مِداد')
@section('content')
<main class="inner-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">المكتبة</a><span>←</span><span>الكتّاب</span><span>←</span><span>{{ $author }}</span></nav>
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> صفحة الكاتب</div><h1>{{ $author }}</h1><p>اقتباسات محفوظة في مكتبة أثر.</p></div>
    <div class="quote-grid">
        @forelse ($quotes as $quote)
            <article class="quote-card"><div class="card-topline"><span class="card-category">{{ $quote->category }}</span><span>{{ $quote->likes_count }} إعجاب</span></div><a class="card-quote-button" href="{{ route('quotes.show', $quote) }}"><span class="card-quote-mark">“</span><span class="card-quote-text">{{ $quote->text }}</span></a><div class="card-bottom"><span>{{ $quote->source ?: 'من مكتبة أثر' }}</span><a class="text-button" href="{{ route('quotes.show', $quote) }}#comments">التفاصيل والتعليقات ↗</a></div></article>
        @empty
            <p class="empty-inline">لا توجد اقتباسات لهذا الكاتب بعد.</p>
        @endforelse
    </div>
    @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
</main>
@endsection
