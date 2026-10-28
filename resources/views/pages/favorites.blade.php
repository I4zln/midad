@extends('layouts.app')
@section('title', 'محفوظاتي — مِداد')
@section('content')
<main class="inner-page">
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> مختاراتك الخاصة</div><h1>محفوظاتي <em>في مِداد.</em></h1><p>الكلمات التي اخترت أن تعود إليها.</p></div>
    <div class="quote-grid">
        @forelse ($quotes as $index => $quote)
            <x-quote-card :quote="$quote" :index="$index" />
        @empty
            <div class="empty-state"><span class="empty-mark">أ</span><h3>مكان لكلمة تختارها.</h3><p>احفظ الاقتباسات التي تلامسك، وستجدها هنا.</p><x-button href="{{ route('quotes.index') }}">اكتشف المكتبة <span aria-hidden="true">←</span></x-button></div>
        @endforelse
    </div>
    @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
</main>
@endsection
