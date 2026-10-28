@extends('layouts.app')
@section('title', 'اقتباسات ' . $author . ' — مِداد')
@section('content')
<main class="inner-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">المكتبة</a><span>←</span><span>الكتّاب</span><span>←</span><span>{{ $author }}</span></nav>
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> صفحة الكاتب</div><h1>{{ $author }}</h1><p>اقتباسات محفوظة في مكتبة أثر.</p></div>
    <div class="quote-grid">
        @forelse ($quotes as $index => $quote)
            <x-quote-card :quote="$quote" :index="$index" />
        @empty
            <p class="empty-inline">لا توجد اقتباسات لهذا الكاتب بعد.</p>
        @endforelse
    </div>
    @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
</main>
@endsection
