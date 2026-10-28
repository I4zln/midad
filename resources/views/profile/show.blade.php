@extends('layouts.app')
@section('title', 'ملف ' . $profile->name . ' — مِداد')
@section('content')
<main class="inner-page profile-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">المكتبة</a><span>←</span><span>الملف الشخصي</span></nav>
    <section class="member-profile">
        <div class="member-cover">
            @if ($profile->cover_path)<img src="{{ asset('storage/'.$profile->cover_path) }}" alt="غلاف الملف الشخصي لـ {{ $profile->name }}">@else<span aria-hidden="true">أ</span>@endif
        </div>
        <div class="member-profile-content">
            <div class="member-profile-top">
                @if ($profile->avatar_path)<img class="member-avatar" src="{{ asset('storage/'.$profile->avatar_path) }}" alt="الصورة الشخصية لـ {{ $profile->name }}">@else<div class="member-avatar member-avatar-fallback" aria-hidden="true">{{ mb_substr($profile->name, 0, 1) }}</div>@endif
                <div class="profile-stats"><span><strong>{{ $quotes->total() }}</strong> اقتباس</span><span><strong>{{ $followers }}</strong> متابع</span><span><strong>{{ $following }}</strong> يتابع</span></div>
                @auth
                    @if (auth()->user()->is($profile))
                        <a class="button button-outline profile-edit-link" href="{{ route('profile.edit') }}">تعديل الملف <span aria-hidden="true">↗</span></a>
                    @else
                        <form method="POST" action="{{ route('users.follow', $profile) }}">@csrf<button class="button {{ $isFollowing ? 'button-outline' : 'button-dark' }}" type="submit">{{ $isFollowing ? 'إلغاء المتابعة' : 'متابعة' }} <span aria-hidden="true">＋</span></button></form>
                    @endif
                @else
                    <a class="button button-outline" href="{{ route('login') }}">سجّل الدخول للمتابعة</a>
                @endauth
            </div>
            <div class="profile-identity"><div class="eyebrow"><span class="eyebrow-line"></span> {{ $profile->is_admin ? 'إدارة مِداد' : 'قارئ في مِداد' }}</div><h1>{{ $profile->name }} @if ($profile->is_admin)<span class="admin-badge">مدير مِداد</span>@endif</h1>@if ($profile->username)<p class="member-username">{{ '@'.$profile->username }}</p>@endif<p>عضو منذ {{ $profile->created_at->format('Y') }}</p></div>
            @if ($profile->bio)<p class="member-bio">{{ $profile->bio }}</p>@endif
            @if ($profile->location || $profile->education)<div class="member-facts">@if ($profile->location)<span>⌖ {{ $profile->location }}</span>@endif @if ($profile->education)<span>✳ {{ $profile->education }}</span>@endif</div>@endif
            @if ($profile->skills)<div class="member-skills" aria-label="المهارات">@foreach ($profile->skills as $skill)<span>{{ $skill }}</span>@endforeach</div>@endif
            @if ($profile->social_links)<div class="member-socials" aria-label="حسابات التواصل">@foreach ($profile->social_links as $network => $url)<a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'x' => 'X'][$network] ?? ucfirst($network) }} ↗</a>@endforeach</div>@endif
        </div>
    </section>
    <div class="section-heading profile-quotes-heading"><div><div class="eyebrow"><span class="eyebrow-line"></span> المساهمات</div><h2>كلمات <em>{{ $profile->name }}</em></h2></div></div>
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
    <div class="quote-grid">
        @forelse ($quotes as $index => $quote)
            <x-quote-card :quote="$quote" :index="$index" />
        @empty
            <p class="empty-inline">لم يضف هذا الحساب اقتباسات بعد.</p>
        @endforelse
    </div>
    @if ($quotes->hasPages())<div class="pagination-links">{{ $quotes->links() }}</div>@endif
</main>
@endsection
