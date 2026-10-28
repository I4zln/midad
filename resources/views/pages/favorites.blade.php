@extends('layouts.app')
@section('title', 'مفضلتي — مِداد')
@section('content')
<main class="inner-page">
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> مختاراتك الخاصة</div><h1>مفضلتي <em>في أثر.</em></h1><p>الكلمات التي اخترت أن تعود إليها.</p></div>
    <div class="quote-grid">
        @forelse ($quotes as $favorite)
            @php($quote = $favorite->quote)
            <article class="quote-card"><div class="card-topline"><span class="card-category">{{ $quote->category }}</span><span>{{ $favorite->created_at->format('Y/m/d') }}</span></div><a class="card-quote-button" href="{{ route('quotes.show', $quote) }}"><span class="card-quote-mark">“</span><span class="card-quote-text">{{ $quote->text }}</span></a><div class="card-bottom"><span class="card-byline"><strong>@if($quote->user)<a href="{{ route('profile.show', $quote->user) }}">{{ $quote->user->name }}</a>@elseif($quote->author)<a href="{{ route('authors.show', ['author' => $quote->author]) }}">{{ $quote->author }}</a>@else كاتب مجهول @endif</strong></span><form method="POST" action="{{ route('quotes.favorite', $quote) }}">@csrf<button class="text-button" type="submit">إزالة من المفضلة</button></form></div></article>
        @empty
            <div class="empty-state"><span class="empty-mark">أ</span><h3>مكان لكلمة تختارها.</h3><p>احفظ الاقتباسات التي تلامسك، وستجدها هنا.</p><a class="button button-dark" href="{{ route('home') }}">اكتشف المكتبة <span aria-hidden="true">←</span></a></div>
        @endforelse
    </div>
    @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
</main>
@endsection
