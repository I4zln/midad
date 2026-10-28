@extends('layouts.app')
@section('title', 'لوحة الإدارة — مِداد')
@section('content')
<main class="inner-page admin-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>لوحة الإدارة</span></nav>
    <header class="admin-header"><div><div class="eyebrow"><span class="eyebrow-line"></span> مساحة المشرف</div><h1>لوحة <em>مِداد.</em></h1><p>مرحبًا {{ auth()->user()->name }}، هذه مساحة متابعة المكتبة وإدارة الاقتباسات.</p></div><span class="admin-badge admin-badge-large">مدير مِداد · @{{ auth()->user()->username }}</span></header>
    <div class="admin-metrics"><article><span>إجمالي الاقتباسات</span><strong>{{ number_format($quoteCount) }}</strong></article><article><span>أعضاء المكتبة</span><strong>{{ number_format($memberCount) }}</strong></article><article><span>ترتيب الأسبوع</span><strong>حسب التفاعل</strong></article></div>
    <section class="admin-leaderboard"><div class="section-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> ترتيب المكتبة</div><h2>الأكثر <em>تفاعلًا.</em></h2></div><p>يُرتّب حسب الإعجابات ثم التعليقات.</p></div>
        <div class="admin-ranking-list">@forelse ($leaderboard as $rank => $quote)<article><span class="leaderboard-rank">{{ sprintf('%02d', $rank + 1) }}</span><a href="{{ route('quotes.show', $quote) }}">{{ \Illuminate\Support\Str::limit($quote->text, 130) }}<small>{{ $quote->user?->name ?: ($quote->author ?: 'كاتب مجهول') }} · {{ $quote->likes_count }} إعجاب · {{ $quote->comments_count }} تعليق</small></a></article>@empty<p class="empty-inline">لا توجد اقتباسات بعد.</p>@endforelse</div>
    </section>
    <section class="admin-quotes"><div class="section-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> الإشراف</div><h2>آخر <em>الاقتباسات.</em></h2></div></div>
        <div class="admin-quote-list">@forelse ($quotes as $quote)<article><div class="admin-quote-meta"><span>{{ $quote->category }}</span><small>{{ $quote->created_at->format('Y-m-d') }}</small></div><a class="admin-quote-text" href="{{ route('quotes.show', $quote) }}">{{ \Illuminate\Support\Str::limit($quote->text, 220) }}</a><div class="admin-quote-footer"><span>{{ $quote->user?->name ?: ($quote->author ?: 'كاتب مجهول') }} · {{ $quote->likes_count }} إعجاب · {{ $quote->comments_count }} تعليق</span><form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return confirm('حذف هذا الاقتباس نهائيًا؟')">@csrf @method('DELETE')<button class="text-button admin-delete" type="submit">حذف الاقتباس</button></form></div></article>@empty<p class="empty-inline">لا توجد اقتباسات في المكتبة.</p>@endforelse</div>
        @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
    </section>
</main>
@endsection
