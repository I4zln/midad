<a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">اكتشف</a>
<a class="nav-link" href="{{ route('home') }}#leaderboard">الأكثر تفاعلًا</a>
<a class="nav-link {{ request()->routeIs('poets.index') ? 'is-active' : '' }}" href="{{ route('poets.index') }}">الشعراء</a>
<a class="nav-link {{ request()->routeIs('muallaqat.*') ? 'is-active' : '' }}" href="{{ route('muallaqat.index') }}">المعلّقات السبع</a>
<a class="nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" href="{{ route('about') }}">من نحن</a>
<a class="nav-link {{ request()->routeIs('creator') ? 'is-active' : '' }}" href="{{ route('creator') }}">عنّي</a>
@auth
    <a class="nav-link {{ request()->routeIs('favorites') ? 'is-active' : '' }}" href="{{ route('favorites') }}">المفضلة</a>
    <a class="nav-link {{ request()->routeIs('notifications') ? 'is-active' : '' }}" href="{{ route('notifications') }}">الإشعارات <span class="nav-count">{{ auth()->user()->unread_notifications_count ?? \App\Models\UserNotification::query()->where('user_id', auth()->id())->whereNull('read_at')->count() }}</span></a>
@endauth
