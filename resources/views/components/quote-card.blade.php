@props(['quote', 'index' => 0, 'feed' => false, 'returnTo' => null])
{{-- feed: used by «اكتشف» — adds the author line and «شارك كصورة», takes extra attributes, and guests return to returnTo after signing in. --}}

@if ($feed)
@php($length = mb_strlen($quote->text))
<article {{ $attributes->class(['quote-card', 'discover-item', 'quote-card-tint' => $index % 2 === 1, 'is-long' => $length > 120 && $length <= 240, 'is-very-long' => $length > 240]) }}>
@else
<article class="quote-card {{ $index % 2 === 1 ? 'quote-card-tint' : '' }}">
@endif
    <div class="card-topline"><span class="card-category">{{ $quote->category }}</span><time datetime="{{ $quote->created_at->toIso8601String() }}">{{ $quote->created_at->format('Y/m/d') }}</time></div>
    <a class="card-quote-button" href="{{ route('quotes.show', $quote) }}" aria-label="قراءة الاقتباس كاملًا">
@if ($feed)<span class="discover-ink" aria-hidden="true"><span class="discover-ink-stroke"></span><span class="discover-pen-track"><span class="discover-pen-lift"><x-fountain-pen class="discover-pen" /></span></span></span>@endif
        <span class="card-quote-mark" aria-hidden="true">“</span><span class="card-quote-text preserve-lines">{{ $quote->text }}</span><span class="quote-read-more">قراءة المزيد</span>
    </a>
@if ($feed && filled($quote->author))<p class="card-author"><span aria-hidden="true">— </span>{{ $quote->author }}</p>@endif
    <div class="quote-card-publisher"><span class="byline-rule"></span><span><strong>{{ $quote->user?->name ?: 'قارئ سابق' }}</strong><small>نُشر {{ $quote->created_at->diffForHumans() }}</small></span></div>
    <div class="card-actions quote-card-actions">
        @auth
            <form method="POST" action="{{ route('quotes.like', $quote) }}" data-like-form>@csrf<button class="like-button {{ $quote->is_liked ?? false ? 'is-liked' : '' }}" type="submit" aria-label="{{ $quote->is_liked ?? false ? 'إزالة الإعجاب' : 'أعجبني' }}" aria-pressed="{{ $quote->is_liked ?? false ? 'true' : 'false' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.2-8.8 10.3-8.8 10.3S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/></svg><span>إعجاب</span><b>{{ $quote->likes_count }}</b></button></form>
            <form method="POST" action="{{ route('quotes.favorite', $quote) }}" data-favorite-form>@csrf<button class="icon-button {{ $quote->is_favorited ?? false ? 'is-favorited' : '' }}" type="submit" aria-pressed="{{ $quote->is_favorited ?? false ? 'true' : 'false' }}" aria-label="{{ $quote->is_favorited ?? false ? 'إزالة من المحفوظات' : 'حفظ الاقتباس' }}" title="{{ $quote->is_favorited ?? false ? 'إزالة من المحفوظات' : 'حفظ الاقتباس' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.75A1.75 1.75 0 0 1 7.75 3h8.5A1.75 1.75 0 0 1 18 4.75V21l-6-3.75L6 21V4.75Z"/></svg></button></form>
        @else
            <a class="like-button" href="{{ route('login', ['next' => $returnTo ?? request()->getRequestUri(), 'reason' => 'like']) }}"{!! $feed ? ' data-guest-action="like"' : '' !!} aria-label="سجّل الدخول للإعجاب">♡ <span>إعجاب</span><b>{{ $quote->likes_count }}</b></a>
            <a class="icon-button" href="{{ route('login', ['next' => $returnTo ?? request()->getRequestUri(), 'reason' => 'save']) }}"{!! $feed ? ' data-guest-action="save"' : '' !!} aria-label="سجّل الدخول لحفظ الاقتباس" title="حفظ الاقتباس"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.75A1.75 1.75 0 0 1 7.75 3h8.5A1.75 1.75 0 0 1 18 4.75V21l-6-3.75L6 21V4.75Z"/></svg></a>
        @endauth
        <button class="icon-button" type="button" data-copy-text="{{ $quote->text }}" aria-label="نسخ الاقتباس" title="نسخ"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"/></svg></button>
        <button class="icon-button" type="button" data-share-url="{{ route('quotes.show', $quote) }}" data-share-title="اقتباس من مِداد" data-share-text="{{ $quote->text }}" aria-label="مشاركة الاقتباس" title="مشاركة"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4m-6.6 7 6.6 4.3"/></svg></button>
@if ($feed)<button class="icon-button" type="button" data-image-share data-text="{{ $quote->text }}" data-author="{{ $quote->author }}" data-source="{{ $quote->source }}" aria-label="شارك الاقتباس كصورة" title="شارك كصورة"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5.5-5.5L6 20"/></svg></button>@endif
        @can('update', $quote)
            <span class="card-owner-actions">
                <a class="icon-button" href="{{ route('quotes.edit', $quote) }}" aria-label="تعديل اقتباسك" title="تعديل"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg></a>
                <form method="POST" action="{{ route('quotes.destroy', $quote) }}" data-confirm="حذف هذا الاقتباس نهائيًا؟ لا يمكن التراجع.">@csrf @method('DELETE')<button class="icon-button card-delete" type="submit" aria-label="حذف اقتباسك" title="حذف"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg></button></form>
            </span>
        @endcan
    </div>
</article>
