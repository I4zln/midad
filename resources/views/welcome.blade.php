@extends('layouts.app')

@section('title', 'مِداد | MIDAD')
@push('head')
    <link rel="preload" href="{{ asset('fonts/amiri/amiri-bold-arabic.woff2') }}" as="font" type="font/woff2" crossorigin>
@endpush

@section('content')
<main>
    <section class="editorial-hero" id="discover">
        <div class="editorial-hero-copy">
            <div class="eyebrow"><span class="eyebrow-line"></span> مجلة أدبية عربية · MIDAD</div>
            <x-ink-welcome />
            <p>مِداد مساحةٌ للكلمة: نقرأ الشعر على مهل، ونحفظ من الكلمات ما يضيء الذاكرة.</p>
            <div class="editorial-hero-actions"><a class="button button-dark" href="#quotes">اكتشف المكتبة <span aria-hidden="true">←</span></a>@auth<button class="button button-outline" type="button" data-open-composer>أضف اقتباسًا <span aria-hidden="true">＋</span> <x-fountain-pen class="pen-icon" /></button>@else<a class="button button-outline" href="{{ route('register') }}">أنشئ حسابًا وانشر اقتباسًا</a>@endauth<a class="button button-outline" href="{{ route('poets.index') }}">استكشف الشعراء</a></div>
        </div>
        @if ($featuredQuote)
            {{-- The newest quote in the library, with who shared it. --}}
            @php($sharer = $featuredQuote->user)
            <aside class="editorial-feature editorial-feature-latest" aria-label="أحدث اقتباس في المكتبة">
                <span class="editorial-feature-mark" aria-hidden="true">❞</span>
                <span class="editorial-feature-label"><span>أحدث اقتباس</span>@if ($featuredQuote->category)<span aria-hidden="true">·</span><span>{{ $featuredQuote->category }}</span>@endif</span>
                <a class="editorial-feature-quote" href="{{ route('quotes.show', $featuredQuote) }}">
                    @php($length = mb_strlen($featuredQuote->text))
                    <blockquote @class(['is-long' => $length > 90, 'is-medium' => $length <= 90 && ($length > 45 || str_contains($featuredQuote->text, "
"))])>{{ $featuredQuote->text }}</blockquote>
                    <span class="sr-only">— اقرأ الاقتباس كاملًا</span>
                </a>
                @if ($featuredQuote->author)
                    <p class="editorial-feature-author">— {{ $featuredQuote->author }}</p>
                @endif
                <div class="editorial-feature-source">
                    @if ($sharer?->avatar_path)
                        <img class="editorial-feature-avatar" src="{{ asset('storage/'.$sharer->avatar_path) }}" alt="" loading="lazy">
                    @else
                        <span class="editorial-feature-avatar" aria-hidden="true">{{ mb_substr($sharer?->name ?: 'ق', 0, 1) }}</span>
                    @endif
                    <div>
                        @if ($sharer)
                            <strong><a href="{{ route('profile.show', $sharer) }}">{{ $sharer->name }}</a></strong>
                        @else
                            <strong>قارئ سابق</strong>
                        @endif
                        <small>شاركه <time datetime="{{ $featuredQuote->created_at->toIso8601String() }}">{{ $featuredQuote->created_at->diffForHumans() }}</time></small>
                    </div>
                </div>
            </aside>
        @else
            <aside class="editorial-feature" aria-label="عن مكتبة مِداد">
                <span class="editorial-feature-mark" aria-hidden="true">❞</span>
                <span class="editorial-feature-label">مكتبة مِداد</span>
                <blockquote>الكلمات التي نحبها تستحق مكانًا نعود إليه.</blockquote>
                <div class="editorial-feature-source"><span></span><div><strong>لأن للكلمات أثرًا يبقى</strong><small>اقرأ، احفظ، وشارك ما يعنيك</small></div></div>
            </aside>
        @endif
        <div class="editorial-hero-foot"><span>01 / 03</span><span>الشعر · الذاكرة · الإنسان</span></div>
    </section>

    <section class="poet-gallery" aria-labelledby="poet-gallery-title">
        <div class="poet-gallery-heading">
            <div><div class="eyebrow"><span class="eyebrow-line"></span> من ذاكرة الشعر العربي</div><h2 id="poet-gallery-title">أصواتٌ <em>لا تغيب.</em></h2></div>
            <div class="muallaqat-intro"><p>المتنبي والبحتري وأبو العلاء المعري. اضغط على الصورة لتظهر أبيات مختارة من شعرهم.</p><a class="text-button" href="{{ route('muallaqat.index') }}">صفحة المعلّقات السبع <span aria-hidden="true">←</span></a></div>
        </div>
        <div class="poet-gallery-grid featured-classical-poets">
            @foreach (config('featured_poets') as $poet)
                <article class="poet-artwork">
                    <button class="poet-gallery-card" type="button" data-poet-reveal aria-expanded="false" aria-label="أظهر أبياتًا من شعر {{ $poet['poet'] }}">
                        <img src="{{ str_starts_with($poet['image'], 'http') ? $poet['image'] : asset($poet['image']) }}" alt="{{ $poet['image_note'] }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/poets/literary-scribe.jpg') }}'">
                        <span class="poet-gallery-shade"></span><span class="poet-gallery-label"><small>من ذاكرة الشعر العربي · اضغط للصورة</small><strong>{{ $poet['poet'] }}</strong><em>{{ $poet['tagline'] }}</em></span>
                        <span class="poet-verse-panel poet-verse-start" aria-hidden="true"><small>{{ $poet['verse_label'] }}</small><span>{{ $poet['verse_start'] }}</span></span>
                        <span class="poet-verse-panel poet-verse-end" aria-hidden="true"><small>ومن أبياتها</small><span>{{ $poet['verse_end'] }}</span></span>
                    </button>
                    <p class="poet-source-note">مرجع الصورة: <a href="{{ $poet['image_source'] }}" target="_blank" rel="noopener noreferrer">{{ $poet['image_note'] }}</a>@if (!empty($poet['verse_source']))<span aria-hidden="true"> · </span>مرجع الأبيات: <a href="{{ $poet['verse_source'] }}" target="_blank" rel="noopener noreferrer">الموسوعة الشعرية</a>@endif</p>
                </article>
            @endforeach
        </div>
    </section>
    <section class="library-authors" aria-labelledby="library-authors-title">
        <div class="poet-gallery-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> من قاعدة المكتبة</div><h2 id="library-authors-title">أصواتٌ <em>كتبها القرّاء.</em></h2></div><a class="text-button" href="{{ route('poets.index') }}">دليل الأسماء كاملًا ↗</a></div>
        <div class="library-authors-list">
            @forelse ($poets as $poet)
                <a class="library-author-link" href="{{ route('authors.show', ['author' => $poet->author]) }}"><span class="author-initial">{{ mb_substr($poet->author, 0, 1) }}</span><strong>{{ $poet->author }}</strong><small>{{ $poet->quotes_count }} {{ $poet->quotes_count === 1 ? 'اقتباس' : 'اقتباسات' }}</small><span aria-hidden="true">↗</span></a>
            @empty
                <p class="empty-inline">ستظهر الأسماء هنا عندما تُضاف اقتباسات إلى المكتبة.</p>
            @endforelse
        </div>
    </section>

    <section class="leaderboard-section" id="leaderboard" aria-labelledby="leaderboard-title">
        <div class="leaderboard-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> نبض المكتبة</div><h2 id="leaderboard-title">الأكثر <em>تفاعلًا.</em></h2></div><p>اقتباسات وصلت إلى القرّاء وحصدت تفاعلهم.</p></div>
        <div class="leaderboard-list">
            @forelse ($leaderboard as $rank => $leaderQuote)
                <article class="leaderboard-item">
                    <span class="leaderboard-rank">{{ sprintf('%02d', $rank + 1) }}</span>
                    <a class="leaderboard-copy" href="{{ route('quotes.show', $leaderQuote) }}"><span class="leaderboard-category">{{ $leaderQuote->category }}</span><span class="leaderboard-text">اقتباس من مساهمات القراء</span><span class="leaderboard-author">{{ $leaderQuote->user?->name ?: 'قارئ سابق' }} @if ($leaderQuote->user?->is_admin)<span class="admin-badge">مدير مِداد</span>@endif</span><span class="leaderboard-open">افتح الاقتباس ↗</span></a>
                    <div class="leaderboard-stats"><span aria-label="الإعجابات">♡ {{ $leaderQuote->likes_count }}</span><span aria-label="التعليقات">◌ {{ $leaderQuote->comments_count }}</span></div>
                </article>
            @empty
                <p class="leaderboard-empty">ستظهر هنا الاقتباسات الأكثر تفاعلًا مع نمو المكتبة.</p>
            @endforelse
        </div>
    </section>

    <section class="library-section" id="quotes" aria-labelledby="latest-quotes-title">
        <header class="section-heading latest-quotes-heading">
            <div><div class="eyebrow"><span class="eyebrow-line"></span> المكتبة</div><h2 id="latest-quotes-title">أحدث <em>الاقتباسات.</em></h2><p>خمس كلمات وصلت حديثًا إلى مِداد.</p></div>
            <x-button href="{{ route('quotes.index') }}" variant="outline">عرض كل الاقتباسات <span aria-hidden="true">←</span></x-button>
        </header>
        <div class="quote-grid" id="quote-grid">
            @forelse ($quotes as $index => $quote)
                <x-quote-card :quote="$quote" :index="$index" />
            @empty
                <div class="empty-state quotes-empty"><span class="empty-mark">“</span><h3>المكتبة تبدأ بكلمة.</h3><p>لا توجد اقتباسات منشورة بعد. شارك أول كلمة في مِداد.</p><x-button href="{{ auth()->check() ? route('quotes.create') : route('register') }}">＋ أضف اقتباسًا <x-fountain-pen class="pen-icon" /></x-button></div>
            @endforelse
        </div>
        <div class="latest-quotes-footer"><span>تعرض أحدث {{ $quotes->count() }} من الاقتباسات</span><a class="text-button" href="{{ route('quotes.index') }}">إلى صفحة الاقتباسات الكاملة ↗</a></div>
    </section>
    <section class="contribute">
        <div class="contribute-number">02 <span>/ 03</span></div>
        <div class="contribute-copy"><div class="eyebrow"><span class="eyebrow-line"></span> لكل كلمة حكاية</div><h2>مرّت بك كلمة<br>ولم تغادرك؟</h2></div>
        <div class="contribute-action"><p>اكتب اقتباسًا ترك أثرًا فيك، وأضف اسم الشاعر أو الكاتب ومصدره إن توفر. بعد حفظه سيظهر في المكتبة، ويأخذ مكانه في واجهة مِداد.</p>@auth<button class="button button-light" type="button" data-open-composer>أضف اقتباسك <span aria-hidden="true">＋</span></button>@else<a class="button button-light" href="{{ route('register') }}">أنشئ حسابًا وشارك اقتباسك</a>@endauth</div>
        <span class="contribute-decoration" aria-hidden="true">أ</span>
    </section>

</main>
@endsection

@auth
@push('dialogs')
<dialog class="composer-dialog" id="composer-dialog" aria-labelledby="composer-title" data-quote-form-errors="{{ $errors->has('text') || $errors->has('author') || $errors->has('source') || $errors->has('category') ? 'true' : 'false' }}">
    <form method="POST" action="{{ route('quotes.store') }}" class="composer-form" data-validate>
        @csrf
        <div class="dialog-heading"><div><span class="eyebrow">مساحة القرّاء</span><h2 id="composer-title">أضف اقتباسًا <em>إلى مِداد.</em></h2></div><button class="icon-button dialog-close" type="button" data-close-dialog aria-label="إغلاق">×</button></div>
        <p class="composer-intro">اكتب النص كما تحب أن يقرؤه الآخرون. سيظهر اسم حسابك بوصفك صاحب المشاركة، واكتب في خانة القائل اسم الشاعر أو الكاتب إن كان معروفًا.</p>
        <x-quote-fields prefix="quote-" />
        <div class="dialog-actions"><x-button>أضف الاقتباس إلى المكتبة <span aria-hidden="true">←</span></x-button><p>سيظهر باسمك في المكتبة، مع المصدر الذي أضفته.</p></div>
    </form>
</dialog>
@endpush
@endauth
