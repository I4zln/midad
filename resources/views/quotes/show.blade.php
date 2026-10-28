@extends('layouts.app')
@section('title', 'اقتباس من مِداد')

@section('content')
<main class="inner-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">المكتبة</a><span>←</span><span>تفاصيل الاقتباس</span></nav>
    <article class="quote-detail">
        <div class="detail-overline"><span>{{ $quote->category }}</span><span>MIDAD · {{ sprintf('%04d', $quote->id) }}</span></div>
        <blockquote class="preserve-lines">{{ $quote->text }}</blockquote>
        <div class="detail-byline">
            <span class="byline-rule"></span>
            <span>
                @if ($quote->user)
                    <a href="{{ route('profile.show', $quote->user) }}">{{ $quote->user->name }}</a>
                @elseif ($quote->author)
                    <a href="{{ route('authors.show', ['author' => $quote->author]) }}">{{ $quote->author }}</a>
                @else
                    كاتب مجهول
                @endif
                <small>{{ $quote->source ?: 'من مكتبة مِداد' }}</small>
            </span>
        </div>
        @can('update', $quote)
            <div class="detail-owner-actions"><a class="button button-outline" href="{{ route('quotes.edit', $quote) }}">تعديل الاقتباس</a><form method="POST" action="{{ route('quotes.destroy', $quote) }}" data-confirm="حذف هذا الاقتباس نهائيًا؟ لا يمكن التراجع.">@csrf @method('DELETE')<button class="button button-outline" type="submit">حذف الاقتباس</button></form></div>
        @endcan
        <div class="detail-social">
            @auth
                <form method="POST" action="{{ route('quotes.like', $quote) }}" data-like-form>@csrf<button class="like-button like-button-detail {{ $quote->is_liked ?? false ? 'is-liked' : '' }}" type="submit" aria-label="{{ $quote->is_liked ?? false ? 'إزالة الإعجاب' : 'إعجاب بهذا الاقتباس' }}" aria-pressed="{{ $quote->is_liked ?? false ? 'true' : 'false' }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.2-8.8 10.3-8.8 10.3S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/></svg><span>أعجبني</span><b>{{ $quote->likes_count }}</b></button></form>
                <form method="POST" action="{{ route('quotes.favorite', $quote) }}" data-favorite-form>@csrf<button class="button button-outline {{ $quote->is_favorited ?? false ? 'is-favorited' : '' }}" type="submit" aria-pressed="{{ $quote->is_favorited ?? false ? 'true' : 'false' }}">{{ $quote->is_favorited ?? false ? 'محفوظ ✓ إزالة من المحفوظات' : 'حفظ في المحفوظات' }}</button></form>
            @else
                <a class="like-button like-button-detail" href="{{ route('login', ['next' => request()->getRequestUri(), 'reason' => 'like']) }}" aria-label="سجّل الدخول للإعجاب"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 8.7c0 5.2-8.8 10.3-8.8 10.3S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/></svg><span>أعجبني</span><b>{{ $quote->likes_count }}</b><small>سجّل للمشاركة</small></a>
            @endauth
            <button class="button button-outline" type="button" data-export-quote data-text="{{ $quote->text }}" data-author="{{ $quote->author ?: ($quote->user?->name ?? 'كاتب مجهول') }}" data-source="{{ $quote->source ?: 'من مكتبة مِداد' }}">تنزيل كصورة ↓</button>
            <button class="button button-outline" type="button" data-copy-link>مشاركة الرابط ↗</button>
        </div>
    </article>

    <section class="comments-section" id="comments">
        <div class="section-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> مساحة الحوار</div><h2>التعليقات <em>({{ $quote->comments_count }})</em></h2></div></div>
        @auth
            <form method="POST" action="{{ route('quotes.comments.store', $quote) }}" class="comment-form" data-validate>
                @csrf
                <x-form-field for="comment-body" name="body" label="أضف كلمة إلى الحوار" help="حتى 1000 حرف، بلغة تحترم الكلمة وأهلها.">
                    <textarea id="comment-body" name="body" maxlength="1000" rows="3" required placeholder="اكتب تعليقك هنا...">{{ old('body') }}</textarea>
                </x-form-field>
                <x-button>نشر التعليق <span aria-hidden="true">←</span></x-button>
            </form>
        @else
            <p class="login-prompt"><a href="{{ route('login', ['next' => request()->getRequestUri().'#comments', 'reason' => 'comment']) }}">سجّل الدخول</a> لتشارك رأيك في هذا الاقتباس.</p>
        @endauth
        <div class="comment-list">
            @forelse ($quote->comments as $comment)
                <article class="comment-item">
                    <div class="comment-meta"><a href="{{ route('profile.show', $comment->user) }}">{{ $comment->user->name }}</a><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time></div>
                    <p>{{ $comment->body }}</p>
                </article>
            @empty
                <p class="empty-inline">لا تعليقات بعد. كن أول من يبدأ حوارًا لطيفًا.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
