@extends('layouts.app')
@section('title', 'لوحة الإدارة — مِداد')
@section('content')
{{-- One screen for the admin: the numbers, every quote with search and delete, and on the side the most liked quotes and the newest members. --}}
<main class="inner-page admin-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>لوحة الإدارة</span></nav>

    <header class="admin-head">
        <div>
            <div class="eyebrow"><span class="eyebrow-line"></span> لوحة الإدارة</div>
            <h1>أهلًا {{ auth()->user()->name }}</h1>
            <p>من هنا تتابع المكتبة: ابحث عن أي اقتباس، وافتحه، أو احذفه إن لم يناسب مِداد.</p>
        </div>
        <span class="admin-badge admin-badge-large">مدير مِداد@if (auth()->user()->username) · <bdi dir="ltr">{{ '@'.auth()->user()->username }}</bdi>@endif</span>
    </header>

    @if (session('resetLink'))
        @php($reset = session('resetLink'))
        {{-- Shown once: the link is not stored anywhere else and stops working after it is used or expires. --}}
        <section class="admin-reset" role="status" aria-labelledby="admin-reset-title">
            <h2 id="admin-reset-title">رابط استعادة كلمة المرور لـ {{ $reset['name'] }}</h2>
            <p>يعمل مرة واحدة خلال {{ $reset['minutes'] }} دقيقة. {{ $reset['whatsapp'] ? 'أرسله إلى رقم الاستعادة المسجّل في حسابه.' : 'لا رقم استعادة في حسابه: تحقّق منه أولًا، ثم أرسله في المحادثة التي راسلك منها.' }}</p>
            @include('admin._verify', ['phone' => $reset['phone'], 'username' => $reset['username'], 'joined' => $reset['joined'], 'quotes' => $reset['quotes'], 'latestQuote' => $reset['latestQuote']])
            <div class="admin-reset-link">
                <input type="text" value="{{ $reset['url'] }}" readonly dir="ltr" aria-label="رابط الاستعادة" data-select-on-focus>
                @if ($reset['whatsapp'])<a class="button button-dark button-small" href="{{ $reset['whatsapp'] }}" target="_blank" rel="noopener noreferrer">أرسل على واتساب إلى رقمه</a>@endif
                <button class="button {{ $reset['whatsapp'] ? 'button-outline' : 'button-dark' }} button-small" type="button" data-copy-text="{{ $reset['url'] }}" data-copy-message="نُسخ رابط الاستعادة.">نسخ الرابط</button>
            </div>
        </section>
    @endif

    @if ($helpRequests->isNotEmpty())
        {{-- Requests from «نسيت كلمة المرور» while this host cannot send email; each one closes when its link is created. --}}
        <section class="admin-panel admin-requests" id="admin-requests" aria-labelledby="admin-requests-title">
            <div class="admin-panel-head"><h2 id="admin-requests-title">طلبات استعادة كلمة المرور</h2><span>{{ $helpRequests->count() }} {{ $helpRequests->count() === 1 ? 'طلب' : 'طلبات' }}</span></div>
            <p class="admin-hint">طلبها أصحابها من صفحة «نسيت كلمة المرور». من له رقم استعادة: أنشئ الرابط وأرسله إلى رقمه مباشرة. ومن ليس له رقم: انتظر رسالته على واتساب وتحقّق منه أولًا.</p>
            <ul class="admin-requests-list">
                @foreach ($helpRequests as $help)
                    @php($member = $help->user)
                    <li class="admin-request">
                        <div class="admin-request-who"><a href="{{ route('profile.show', $member) }}">{{ $member->name }}</a><small dir="ltr">{{ $member->email }}</small><small>طلب {{ $help->updated_at->diffForHumans() }}</small></div>
                        @include('admin._verify', ['phone' => $member->recovery_phone, 'username' => $member->username, 'joined' => $member->created_at?->format('Y/m/d'), 'quotes' => $member->quotes_count, 'latestQuote' => $member->latestQuote ? \Illuminate\Support\Str::limit($member->latestQuote->text, 60) : null])
                        <div class="admin-request-actions">
                            <form method="POST" action="{{ route('admin.users.reset-link', $member) }}">@csrf<button class="button button-dark button-small" type="submit">{{ $member->recovery_phone ? 'أنشئ الرابط وأرسله لرقمه' : 'أنشئ رابط الاستعادة' }}</button></form>
                            <form method="POST" action="{{ route('admin.help-requests.close', $help) }}">@csrf<button class="admin-action" type="submit" title="أغلق الطلب دون رابط">تمّ</button></form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <dl class="admin-stats">
        <div><dt>الاقتباسات</dt><dd>{{ number_format($stats['quotes']) }}</dd></div>
        <div><dt>الأعضاء</dt><dd>{{ number_format($stats['members']) }}</dd></div>
        <div><dt>الإعجابات</dt><dd>{{ number_format($stats['likes']) }}</dd></div>
        <div><dt>جديد هذا الأسبوع</dt><dd>{{ number_format($stats['week']) }}</dd></div>
    </dl>

    <div class="admin-layout">
        <section class="admin-panel admin-manage" aria-labelledby="admin-quotes-title">
            <div class="admin-panel-head">
                <h2 id="admin-quotes-title">الاقتباسات</h2>
                <span>{{ number_format($quotes->total()) }} {{ $search !== '' || $category !== '' ? 'نتيجة' : 'اقتباس' }}</span>
            </div>

            <form class="admin-filter" method="GET" action="{{ route('admin.index') }}" role="search">
                @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
                <label class="search-box"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg><input name="q" type="search" value="{{ $search }}" placeholder="ابحث في النص أو الكاتب أو الناشر" aria-label="ابحث في الاقتباسات"><button class="search-submit" type="submit" aria-label="بحث">↵</button></label>
                <div class="category-list" role="group" aria-label="التصنيف">
                    <a class="category-chip {{ $category === '' ? 'is-selected' : '' }}" href="{{ route('admin.index', array_filter(['q' => $search])) }}">الكل</a>
                    @foreach ($categories as $key => $label)<a class="category-chip {{ $category === $key ? 'is-selected' : '' }}" href="{{ route('admin.index', array_filter(['q' => $search, 'category' => $key])) }}">{{ $label }}</a>@endforeach
                </div>
                @if ($search !== '' || $category !== '')<a class="clear-filters" href="{{ route('admin.index') }}">مسح البحث ×</a>@endif
            </form>

            @if ($quotes->isEmpty())
                <p class="admin-empty">{{ $search !== '' || $category !== '' ? 'لا توجد اقتباسات مطابقة. امسح البحث وحاول مجددًا.' : 'لا توجد اقتباسات في المكتبة بعد.' }}</p>
            @else
                <ul class="admin-rows">
                    @foreach ($quotes as $quote)
                        <li class="admin-row">
                            <div class="admin-row-main">
                                <a class="admin-row-text" href="{{ route('quotes.show', $quote) }}">{{ \Illuminate\Support\Str::limit($quote->text, 160) }}</a>
                                <p class="admin-row-meta">
                                    <span class="admin-row-category">{{ $categories[$quote->category] ?? $quote->category }}</span>
                                    <span>{{ $quote->author ?: 'دون كاتب' }}</span>
                                    <span>نشره {{ $quote->user?->name ?: 'قارئ سابق' }}</span>
                                    <time datetime="{{ $quote->created_at->toDateString() }}">{{ $quote->created_at->format('Y/m/d') }}</time>
                                    <span>{{ $quote->likes_count }} إعجاب · {{ $quote->comments_count }} تعليق</span>
                                </p>
                            </div>
                            <div class="admin-row-actions">
                                <a class="admin-action" href="{{ route('quotes.show', $quote) }}">عرض</a>
                                <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" data-confirm="حذف هذا الاقتباس من المكتبة نهائيًا؟ لا يمكن التراجع.">@csrf @method('DELETE')<button class="admin-action admin-delete" type="submit" aria-label="حذف الاقتباس: {{ \Illuminate\Support\Str::limit($quote->text, 40) }}">حذف</button></form>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
            @endif
        </section>

        <aside class="admin-side">
            <section class="admin-panel" aria-labelledby="admin-top-title">
                <div class="admin-panel-head"><h2 id="admin-top-title">الأكثر إعجابًا</h2></div>
                @if ($top->isEmpty())
                    <p class="admin-empty">لا توجد اقتباسات بعد.</p>
                @else
                    <ol class="admin-top">
                        @foreach ($top as $rank => $quote)
                            <li><span class="admin-top-rank">{{ $rank + 1 }}</span><a href="{{ route('quotes.show', $quote) }}">{{ \Illuminate\Support\Str::limit($quote->text, 70) }}</a><span class="admin-top-count">{{ $quote->likes_count }} ♡</span></li>
                        @endforeach
                    </ol>
                @endif
            </section>

            <section class="admin-panel" id="admin-members" aria-labelledby="admin-members-title">
                <div class="admin-panel-head"><h2 id="admin-members-title">{{ $memberSearch !== '' ? 'الأعضاء' : 'أحدث الأعضاء' }}</h2></div>
                {{-- Find a member who asked for help signing in, then hand them a reset link (email cannot leave this host). --}}
                <form class="admin-member-search" method="GET" action="{{ route('admin.index') }}#admin-members" role="search">
                    @foreach (array_filter(['q' => $search, 'category' => $category]) as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
                    <label class="search-box"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg><input name="member" type="search" value="{{ $memberSearch }}" placeholder="ابحث بالاسم أو البريد" aria-label="ابحث عن عضو"><button class="search-submit" type="submit" aria-label="بحث">↵</button></label>
                </form>
                <p class="admin-hint">راسلك أحدهم على واتساب دون أن يطلب من الموقع؟ ابحث عنه واضغط «رابط استعادة»، وتحقّق منه في الصندوق أعلى الصفحة قبل أن ترسل.</p>
                @if ($members->isEmpty())
                    <p class="admin-empty">لا يوجد عضو بهذا الاسم أو البريد.</p>
                @else
                    <ul class="admin-members">
                        @foreach ($members as $member)
                            <li>
                                <span class="account-initial" aria-hidden="true">{{ mb_substr($member->name, 0, 1) }}</span>
                                <span class="admin-member-text"><a href="{{ route('profile.show', $member) }}">{{ $member->name }}</a>@if ($member->is_admin) <span class="admin-badge">مدير</span>@endif<small dir="ltr">{{ $member->email }}</small><small>انضم {{ $member->created_at?->diffForHumans() }} · {{ $member->quotes_count }} اقتباس</small></span>
                                <form method="POST" action="{{ route('admin.users.reset-link', $member) }}">@csrf<button class="admin-action admin-member-reset" type="submit" aria-label="رابط استعادة كلمة المرور لـ {{ $member->name }}">رابط استعادة</button></form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if ($memberSearch !== '')<a class="clear-filters" href="{{ route('admin.index', array_filter(['q' => $search, 'category' => $category])) }}#admin-members">عرض أحدث الأعضاء ×</a>@endif
            </section>
        </aside>
    </div>
</main>
@endsection
