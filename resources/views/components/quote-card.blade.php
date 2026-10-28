@props(['quote', 'index' => 0])

<article class="quote-card {{ $index % 2 === 1 ? 'quote-card-tint' : '' }}">
    <div class="card-topline"><span class="card-category">{{ $quote->category }}</span><time datetime="{{ $quote->created_at->toIso8601String() }}">{{ $quote->created_at->format('Y/m/d') }}</time></div>
    <a class="card-quote-button" href="{{ route('quotes.show', $quote) }}" aria-label="قراءة الاقتباس كاملًا">
        <span class="card-quote-mark" aria-hidden="true">“</span><span class="card-quote-text preserve-lines">{{ $quote->text }}</span><span class="quote-read-more">قراءة المزيد</span>
    </a>
    <div class="quote-card-publisher"><span class="byline-rule"></span><span><strong>{{ $quote->user?->name ?: 'قارئ سابق' }}</strong><small>نُشر {{ $quote->created_at->diffForHumans() }}</small></span></div>
    <div class="card-actions quote-card-actions">
        @auth
            <form method="POST" action="{{ route('quotes.like', $quote) }}" data-like-form>@csrf<button class="like-button {{ $quote->is_liked ?? false ? 'is-liked' : '' }}" type="submit" aria-label="{{ $quote->is_liked ?? false ? 'إزالة الإعجاب' : 'أعجبني' }}" aria-pressed="{{ $quote->is_liked ?? false ? 'true' : 'false' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.2-8.8 10.3-8.8 10.3S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/></svg><span>إعجاب</span><b>{{ $quote->likes_count }}</b></button></form>
            <form method="POST" action="{{ route('quotes.favorite', $quote) }}">@csrf<button class="icon-button {{ $quote->is_favorited ?? false ? 'is-favorited' : '' }}" type="submit" aria-label="{{ $quote->is_favorited ?? false ? 'إزالة من المحفوظات' : 'حفظ الاقتباس' }}" title="{{ $quote->is_favorited ?? false ? 'إزالة من المحفوظات' : 'حفظ الاقتباس' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.75A1.75 1.75 0 0 1 7.75 3h8.5A1.75 1.75 0 0 1 18 4.75V21l-6-3.75L6 21V4.75Z"/></svg></button></form>
        @else
            <a class="like-button" href="{{ route('login') }}" aria-label="سجّل الدخول للإعجاب">♡ <span>إعجاب</span><b>{{ $quote->likes_count }}</b></a>
            <a class="icon-button" href="{{ route('login') }}" aria-label="سجّل الدخول لحفظ الاقتباس" title="حفظ الاقتباس"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4.75A1.75 1.75 0 0 1 7.75 3h8.5A1.75 1.75 0 0 1 18 4.75V21l-6-3.75L6 21V4.75Z"/></svg></a>
        @endauth
        <button class="icon-button" type="button" data-copy-text="{{ $quote->text }}" aria-label="نسخ الاقتباس" title="نسخ"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"/></svg></button>
        <button class="icon-button" type="button" data-share-url="{{ route('quotes.show', $quote) }}" data-share-title="اقتباس من مِداد" data-share-text="{{ $quote->text }}" aria-label="مشاركة الاقتباس" title="مشاركة"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4m-6.6 7 6.6 4.3"/></svg></button>
        @if (auth()->check() && auth()->id() === $quote->user_id)
            <a class="icon-button" href="{{ route('quotes.edit', $quote) }}" aria-label="تعديل اقتباسك" title="تعديل">✎</a>
        @endif
    </div>
</article>
