@extends('layouts.app')
@section('title', 'اكتشف — مِداد | MIDAD')
@section('content')
@php
    $tabs = ['for-you' => auth()->check() ? 'مختارة لك' : 'مختارات', 'latest' => 'الأحدث', 'popular' => 'الأشهر'];
    $link = fn (array $change) => route('discover', array_filter(array_merge(['tab' => $tab !== 'for-you' ? $tab : null, 'category' => $category ?: null], $change)));
    $startMode = $mode ?? 'focus';
@endphp
{{-- How it ranks: app/Services/FeedRanker.php and docs/FEED_ALGORITHM.md. Behaviour: resources/js/discover.js. --}}
<main class="discover-page" data-discover data-mode="{{ $startMode }}"
    data-feed-url="{{ route('discover.feed', array_filter(['tab' => $tab, 'category' => $category ?: null])) }}" data-next="{{ $next }}"
    @auth data-mode-url="{{ route('discover.mode') }}" @endauth @if ($mode) data-mode-account @endif>
    @unless ($mode)
        {{-- Apply this browser's saved view before first paint (members who chose one get it from their account). --}}
        <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">(function () { var m; try { m = localStorage.getItem('midad-discover-mode'); } catch (e) {} if (m === 'focus' || m === 'timeline') document.currentScript.parentElement.dataset.mode = m; })();</script>
    @endunless
    <header class="discover-bar">
        <div class="discover-bar-row">
            <h1 class="discover-title">اكتشف</h1>
            <nav class="discover-tabs" aria-label="ترتيب الاقتباسات">
                @foreach ($tabs as $key => $label)
                    <a class="discover-tab {{ $tab === $key ? 'is-active' : '' }}" href="{{ $link(['tab' => $key !== 'for-you' ? $key : null]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="discover-modes" role="group" aria-label="طريقة العرض">
                <button type="button" data-discover-mode="focus" aria-pressed="{{ $startMode === 'focus' ? 'true' : 'false' }}" title="اقتباس واحد في كل شاشة"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9.5 10h5M9.5 13.5h3"/></svg><span>تأمّل</span></button>
                <button type="button" data-discover-mode="timeline" aria-pressed="{{ $startMode === 'timeline' ? 'true' : 'false' }}" title="قائمة متصلة من البطاقات"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3.5" width="16" height="7" rx="1.5"/><rect x="4" y="13.5" width="16" height="7" rx="1.5"/></svg><span>الخط الزمني</span></button>
            </div>
        </div>
        <div class="discover-bar-row">
            <div class="category-list discover-chips" role="group" aria-label="التصنيف">
                <a class="category-chip {{ $category === '' ? 'is-selected' : '' }}" href="{{ $link(['category' => null]) }}" @if ($category === '') aria-current="true" @endif>الكل</a>
                @foreach ($categories as $key => $label)
                    <a class="category-chip {{ $category === $key ? 'is-selected' : '' }}" href="{{ $link(['category' => $key]) }}" @if ($category === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </div>
            <p class="discover-keys" id="discover-keys"><kbd>↑</kbd> <kbd>↓</kbd> أو <kbd>J</kbd> <kbd>K</kbd> للتنقّل</p>
        </div>
    </header>

    <div class="discover-stream" data-discover-stream role="feed" aria-busy="false" aria-label="اقتباسات اكتشف" aria-describedby="discover-keys" tabindex="0">
        @include('discover._items', ['quotes' => $quotes, 'tab' => $tab, 'category' => $category])
        @if ($quotes->isEmpty())
            <div class="empty-state discover-empty"><span class="empty-mark" aria-hidden="true">“</span><h2>لا توجد اقتباسات هنا بعد.</h2><p>{{ $category ? 'جرّب تصنيفًا آخر أو عُد إلى «الكل».' : 'كن أول من يشارك كلمة في مِداد.' }}</p>
                @if ($category)<x-button href="{{ $link(['category' => null]) }}" variant="outline">عرض الكل</x-button>@else<x-button href="{{ auth()->check() ? route('quotes.create') : route('register') }}">＋ أضف اقتباسًا</x-button>@endif
            </div>
        @endif
        <div class="discover-sentinel" data-discover-sentinel data-status="{{ $quotes->isEmpty() ? 'empty' : ($next ? 'idle' : 'end') }}" aria-live="polite">
            <p class="discover-loading"><span class="discover-dots" aria-hidden="true"><i></i><i></i><i></i></span> نُحضِر كلمات أخرى…</p>
            <div class="discover-error"><p>تعذّر تحميل المزيد. تحقّق من الاتصال ثم حاول مجددًا.</p><button class="button button-outline button-small" type="button" data-discover-retry>حاول مجددًا</button></div>
            <div class="discover-end"><span class="empty-mark" aria-hidden="true">“</span><h2>قرأت كل ما هنا.</h2><p>{{ $category ? 'جرّب تصنيفًا آخر، أو ابدأ القائمة من جديد.' : 'ابدأ القائمة من جديد، أو تصفّح المكتبة كاملة.' }}</p>
                <div class="discover-end-actions"><a class="button button-dark button-small" href="{{ $link([]) }}">ابدأ من جديد</a><a class="button button-outline button-small" href="{{ route('quotes.index') }}">كل الاقتباسات</a></div>
            </div>
        </div>
    </div>
    @if ($total > 0)
        {{-- «تأمّل»: where the reader is, with a step up and down (the same as ↑ ↓ and J K). --}}
        <nav class="discover-stepper" data-discover-stepper aria-label="التنقّل بين الاقتباسات">
            <button class="icon-button" type="button" data-discover-step="-1" aria-label="الاقتباس السابق" title="السابق"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg></button>
            <p><span data-discover-position>1</span> من {{ number_format($total) }}</p>
            <button class="icon-button" type="button" data-discover-step="1" aria-label="الاقتباس التالي" title="التالي"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg></button>
        </nav>
    @endif

    @guest
        {{-- Asked before leaving the feed; without JavaScript the like/save links go straight to the login page. --}}
        <dialog class="confirm-dialog discover-login" id="discover-login" aria-labelledby="discover-login-title" aria-describedby="discover-login-reason">
            <form method="dialog" class="confirm-dialog-body">
                <h2 id="discover-login-title">سجّل الدخول أولًا</h2>
                <p id="discover-login-reason" data-reason-like="سجّل الدخول ليُحسب إعجابك بهذا الاقتباس، وسنعيدك إليه مباشرة." data-reason-save="سجّل الدخول لتحفظ هذا الاقتباس في محفوظاتك، وسنعيدك إليه مباشرة."></p>
                <div class="confirm-dialog-actions">
                    <button class="button button-outline" value="cancel">ليس الآن</button>
                    <a class="button button-dark" href="{{ route('login') }}" data-discover-login>تسجيل الدخول</a>
                </div>
                <p class="discover-login-register">ليس لديك حساب؟ <a href="{{ route('register') }}" data-discover-register>أنشئ حسابًا</a></p>
            </form>
        </dialog>
    @endguest
</main>
@endsection
