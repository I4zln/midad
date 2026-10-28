@extends('layouts.app')
@section('title', 'الإشعارات — مِداد')
@section('content')
<main class="inner-page notifications-page">
    <div class="notifications-heading">
        <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> جديدك في أثر</div><h1>الإشعارات.</h1><p>تفاعلات القراء مع كلماتك ومساهماتك.</p></div>
        @if ($notifications->whereNull('read_at')->count())
            <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="button button-outline" type="submit">تعليم الكل كمقروء</button></form>
        @endif
    </div>
    <div class="notification-list">
        @forelse ($notifications as $notification)
            <article class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                <span class="notification-dot" aria-hidden="true"></span>
                <div><p><a href="{{ $notification->actor ? route('profile.show', $notification->actor) : route('home') }}">{{ $notification->actor?->name ?? 'قارئ' }}</a> {{ $notification->message }}</p>
                    @if ($notification->quote)<a class="notification-quote" href="{{ route('quotes.show', $notification->quote) }}">«{{ \Illuminate\Support\Str::limit($notification->quote->text, 110) }}»</a>@endif
                    <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                </div>
            </article>
        @empty
            <div class="empty-state"><span class="empty-mark">أ</span><h3>لا إشعارات جديدة.</h3><p>ستجد هنا تفاعلات القراء معك.</p><a class="button button-dark" href="{{ route('home') }}">العودة إلى المكتبة <span aria-hidden="true">←</span></a></div>
        @endforelse
    </div>
    @if ($notifications->hasPages())<div class="pagination-links">{{ $notifications->links() }}</div>@endif
</main>
@endsection
