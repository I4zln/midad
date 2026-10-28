@extends('layouts.app')
@section('title', 'كل الاقتباسات — مِداد | MIDAD')
@section('content')
<main class="inner-page quotes-index-page">
    <header class="quotes-index-heading">
        <div><div class="eyebrow"><span class="eyebrow-line"></span> مكتبة مِداد</div><h1>كل <em>الاقتباسات.</em></h1><p>تصفّح الكلمات التي شاركها القرّاء، مرتبة من الأحدث إلى الأقدم.</p><span class="quotes-total">{{ number_format($totalQuotes) }} اقتباسًا في المكتبة</span></div>
        <x-button href="{{ auth()->check() ? route('quotes.create') : route('register') }}">＋ أضف اقتباسًا</x-button>
    </header>
    <form class="quotes-filters" method="GET" action="{{ route('quotes.index') }}">
        @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
        <label class="search-box"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg><input id="quote-search" name="q" type="search" value="{{ $search }}" placeholder="ابحث في النص أو الكاتب أو المصدر" aria-label="ابحث في الاقتباسات"><button class="search-submit" type="submit" aria-label="بحث">↵</button></label>
        <div class="category-list" role="group" aria-label="التصنيف">
            <a class="category-chip {{ $activeCategory === '' ? 'is-selected' : '' }}" href="{{ route('quotes.index', array_filter(['q' => $search])) }}">الكل</a>
            @foreach ($categories as $key => $label)<a class="category-chip {{ $activeCategory === $key ? 'is-selected' : '' }}" href="{{ route('quotes.index', array_filter(['q' => $search, 'category' => $key])) }}">{{ $label }}</a>@endforeach
        </div>
        @if ($search !== '' || $activeCategory !== '')<a class="clear-filters" href="{{ route('quotes.index') }}">مسح البحث والفلاتر ×</a>@endif
    </form>
    <p class="quotes-result-count">{{ $quotes->total() }} نتيجة</p>
    <div class="quote-grid quotes-index-grid">
        @forelse ($quotes as $index => $quote)
            <x-quote-card :quote="$quote" :index="$index" />
        @empty
            <div class="empty-state quotes-empty"><span class="empty-mark">“</span><h2>{{ $search || $activeCategory ? 'لا توجد نتائج مطابقة.' : 'المكتبة تبدأ بكلمة.' }}</h2><p>{{ $search || $activeCategory ? 'امسح البحث أو غيّر التصنيف وحاول مجددًا.' : 'لا توجد اقتباسات منشورة بعد. كن أول من يشارك كلمة.' }}</p><x-button href="{{ auth()->check() ? route('quotes.create') : route('register') }}">＋ أضف اقتباسًا</x-button></div>
        @endforelse
    </div>
    @if ($quotes->hasPages())<nav class="pagination-links" aria-label="ترقيم صفحات الاقتباسات">{{ $quotes->links() }}</nav>@endif
</main>
@endsection
