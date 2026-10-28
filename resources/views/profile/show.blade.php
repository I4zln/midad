@extends('layouts.app')
@section('title', 'ملف ' . $profile->name . ' — مِداد')
@section('content')
@php
    $tabs = ['quotes' => [$isOwner ? 'اقتباساتي' : 'الاقتباسات', $stats['quotes']]];
    if ($isOwner) {
        $tabs['liked'] = ['المعجب بها', $stats['liked']];
        $tabs['saved'] = ['المحفوظات', $stats['saved']];
    }
@endphp
<main class="inner-page profile-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><span>{{ $isOwner ? 'ملفي' : 'الملف الشخصي' }}</span></nav>

    <section class="member-profile" aria-labelledby="member-name">
        <div class="member-cover @if (! $profile->cover_path) is-empty @endif">
            @if ($profile->cover_path)<img src="{{ asset('storage/'.$profile->cover_path) }}" alt="" loading="lazy">@endif
        </div>
        <div class="member-head">
            @if ($profile->avatar_path)
                <img class="member-avatar" src="{{ asset('storage/'.$profile->avatar_path) }}" alt="الصورة الشخصية لـ {{ $profile->name }}">
            @else
                <span class="member-avatar member-avatar-fallback" aria-hidden="true">{{ mb_substr($profile->name, 0, 1) }}</span>
            @endif
            <div class="member-identity">
                <span class="eyebrow"><span class="eyebrow-line"></span> {{ $profile->is_admin ? 'إدارة مِداد' : 'من قرّاء مِداد' }}</span>
                <h1 id="member-name">{{ $profile->name }} @if ($profile->is_admin)<span class="admin-badge">مدير مِداد</span>@endif</h1>
                <p class="member-meta">
                    @if ($profile->username)<span dir="ltr">{{ '@'.$profile->username }}</span><span aria-hidden="true">·</span>@endif
                    <span>عضو منذ {{ $profile->created_at->translatedFormat('F Y') }}</span>
                </p>
            </div>
            <div class="member-actions">
                @if ($isOwner)
                    <x-button href="{{ route('quotes.create') }}">＋ أضف اقتباسًا <x-fountain-pen class="pen-icon" /></x-button>
                    <x-button href="{{ route('profile.edit') }}" variant="outline">تعديل الملف</x-button>
                @elseif (auth()->check())
                    <form method="POST" action="{{ route('users.follow', $profile) }}">@csrf<x-button :variant="$isFollowing ? 'outline' : 'dark'">{{ $isFollowing ? 'إلغاء المتابعة' : 'متابعة' }}</x-button></form>
                @else
                    <x-button href="{{ route('login', ['next' => request()->getRequestUri(), 'reason' => 'follow']) }}" variant="outline">سجّل الدخول للمتابعة</x-button>
                @endif
            </div>
        </div>

        @if ($profile->bio || $profile->location || $profile->education || $profile->skills || $profile->social_links)
            <div class="member-about">
                @if ($profile->bio)<p class="member-bio">{{ $profile->bio }}</p>@endif
                @if ($profile->location || $profile->education)<p class="member-facts">@if ($profile->location)<span>{{ $profile->location }}</span>@endif @if ($profile->education)<span>{{ $profile->education }}</span>@endif</p>@endif
                @if ($profile->skills)<div class="member-skills" aria-label="المهارات">@foreach ($profile->skills as $skill)<span>{{ $skill }}</span>@endforeach</div>@endif
                @if ($profile->social_links)<div class="member-socials" aria-label="حسابات التواصل">@foreach ($profile->social_links as $network => $url)<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'x' => 'X'][$network] ?? ucfirst($network) }} ↗</a>@endforeach</div>@endif
            </div>
        @elseif ($isOwner)
            <p class="member-about member-about-hint">أضف نبذة قصيرة عنك من <a href="{{ route('profile.edit') }}">تعديل الملف</a> ليتعرّف عليك القرّاء.</p>
        @endif

        <dl class="member-stats">
            <div><dt>اقتباسات</dt><dd>{{ number_format($stats['quotes']) }}</dd></div>
            <div><dt>إعجابات على الاقتباسات</dt><dd>{{ number_format($stats['likes_received']) }}</dd></div>
            <div><dt>المتابِعون</dt><dd>{{ number_format($followers) }}</dd></div>
            <div><dt>قيد المتابعة</dt><dd>{{ number_format($following) }}</dd></div>
        </dl>
    </section>

    @if ($specialPending ?? false)
        <aside class="special-profile-message special-profile-pending" aria-labelledby="special-pending-title">
            <div class="special-message-ornament" aria-hidden="true">✦</div>
            <div class="eyebrow"><span class="eyebrow-line"></span> رسالة خاصة بانتظارك</div>
            <h2 id="special-pending-title">أكّد بريدك لتظهر لك</h2>
            @if ($mailReady)
                <p>هذه الرسالة لصاحب هذا البريد وحده، لذلك نتأكد منه أولًا. أرسل رابط التأكيد ثم افتحه من بريدك، وستظهر الرسالة هنا.</p>
                <form method="POST" action="{{ route('verification.send') }}">@csrf<x-button>أرسل رابط التأكيد</x-button></form>
            @else
                <p>هذه الرسالة لصاحب هذا البريد وحده، لذلك تظهر بعد تأكيده. خدمة البريد غير متصلة الآن، فلا يلزمك شيء حاليًا؛ تصفّح مِداد كالمعتاد، وسيصلك رابط التأكيد حين تُفعَّل.</p>
            @endif
        </aside>
    @endif

    @if ($specialMessage)
        <aside class="special-profile-message" aria-labelledby="special-profile-message-title">
            <div class="special-message-ornament" aria-hidden="true">✦</div>
            <div class="eyebrow"><span class="eyebrow-line"></span> رسالة خاصة لك</div>
            <h2 id="special-profile-message-title">{{ $specialMessage->title }}</h2>
            <blockquote dir="rtl">{{ $specialMessage->body }}</blockquote>
            <p class="special-message-signature">من {{ $specialMessage->sender_name }} · {{ $specialMessage->sender_role }}</p>
            <small class="special-message-source">الأبيات للشاعر جرير · <a href="https://dorar.net/arabia/6057/%D8%A7%D9%84%D9%85%D8%B7%D9%84%D8%A8-%D8%A7%D9%84%D8%A3%D9%88%D9%84-%D8%AC%D8%B1%D9%8A%D8%B1" target="_blank" rel="noopener noreferrer">مرجع النص</a></small>
        </aside>
    @endif

    <nav class="profile-tabs" aria-label="أقسام الملف">
        @foreach ($tabs as $key => [$label, $count])
            <a class="profile-tab {{ $tab === $key ? 'is-active' : '' }}" href="{{ route('profile.show', $key === 'quotes' ? $profile : [$profile, 'tab' => $key]) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }} <span>{{ number_format($count) }}</span></a>
        @endforeach
    </nav>

    <div class="quote-grid profile-quote-grid">
        @forelse ($quotes as $index => $quote)
            <x-quote-card :quote="$quote" :index="$index" />
        @empty
            <div class="empty-state profile-empty">
                <span class="empty-mark" aria-hidden="true">“</span>
                @if ($tab === 'liked')
                    <h2>لم تُعجب بأي اقتباس بعد.</h2><p>اضغط «إعجاب» على الاقتباسات التي تلامسك، وستجدها هنا.</p>
                    <x-button href="{{ route('quotes.index') }}" variant="outline">تصفّح الاقتباسات</x-button>
                @elseif ($tab === 'saved')
                    <h2>لا محفوظات بعد.</h2><p>اضغط رمز الحفظ على أي اقتباس لتعود إليه من هنا.</p>
                    <x-button href="{{ route('quotes.index') }}" variant="outline">تصفّح الاقتباسات</x-button>
                @elseif ($isOwner)
                    <h2>لم تنشر اقتباسًا بعد.</h2><p>اكتب كلمة تركت فيك أثرًا؛ تحتاج دقيقة واحدة.</p>
                    <x-button href="{{ route('quotes.create') }}">＋ أضف اقتباسًا <x-fountain-pen class="pen-icon" /></x-button>
                @else
                    <h2>لا توجد اقتباسات منشورة في هذا الملف بعد.</h2><p>عُد لاحقًا، أو تصفّح ما شاركه القرّاء الآخرون.</p>
                    <x-button href="{{ route('quotes.index') }}" variant="outline">تصفّح الاقتباسات</x-button>
                @endif
            </div>
        @endforelse
    </div>
    @if ($quotes->hasPages())<nav class="pagination-links" aria-label="صفحات الاقتباسات">{{ $quotes->links() }}</nav>@endif
</main>
@endsection
