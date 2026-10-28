<header class="topbar">
            <a class="brand" href="{{ route('home') }}" aria-label="مِداد | MIDAD، الصفحة الرئيسية">
                <span class="brand-logo"><span class="brand-mark">مِـداد</span><span class="brand-latin">MIDAD</span></span>
            </a>
            <nav class="main-nav" aria-label="التنقل الرئيسي">
                <a class="nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">الرئيسية</a>
                <a class="nav-link {{ request()->routeIs('quotes.*') ? 'is-active' : '' }}" href="{{ route('quotes.index') }}">الاقتباسات</a>
                <a class="nav-link {{ request()->routeIs('poets.*') || request()->routeIs('muallaqat.*') ? 'is-active' : '' }}" href="{{ route('poets.index') }}">الشعراء</a>
                <a class="nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" href="{{ route('about') }}">من نحن</a>
            </nav>
            <div class="topbar-actions">
                <details class="ambient-menu">
                    <summary class="ambient-summary" aria-label="أجواء صوتية اختيارية" title="أجواء صوتية اختيارية"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/></svg><span>الأجواء</span></summary>
                <div class="ambient-player" aria-label="مشغل الصوت الخلفي">
                    <p class="ambient-note">صوت اختياري، لا يعمل إلا إذا اخترته.</p>
                    <label class="audio-choice-label" for="ethraa-audio-choice">اختر الأجواء</label>
                    <select id="ethraa-audio-choice" class="audio-choice" aria-label="اختر أجواء الاستماع">
                        <option value="">اختر صوتًا</option>
                        <option value="islamic" data-src="{{ asset('audio/islamic.mp3') }}">أصوات إسلامية</option>
                        <option value="oud" data-src="{{ asset('audio/literary-oud.mp3') }}">موسيقى أدبية</option>
                        <option value="darwish" data-src="{{ asset('audio/mahmoud-darwish.mp3') }}">محمود درويش</option>
                        <option value="silent">صامت</option>
                    </select>
                    <button class="audio-toggle" id="ethraa-audio-toggle" type="button" aria-pressed="false" aria-label="تشغيل الصوت المختار" disabled>
                        <svg class="audio-toggle-play" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 10 6-10 6V6Z"/></svg>
                        <svg class="audio-toggle-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6v12M16 6v12"/></svg>
                        <span>تشغيل</span>
                    </button>
                    <button class="audio-mute" id="ethraa-audio-mute" type="button" aria-pressed="false" aria-label="كتم الصوت" disabled>♪</button>
                    <label class="audio-volume-label" for="ethraa-audio-volume">مستوى الصوت</label>
                    <input id="ethraa-audio-volume" class="audio-volume" type="range" min="0" max="0.18" step="0.01" value="0.09" aria-label="مستوى الصوت" disabled>
                    <audio id="ethraa-audio" preload="none" loop></audio>
                </div>
                </details>
                <button class="icon-button" id="theme-toggle" type="button" aria-label="تبديل الوضع الداكن" title="تبديل المظهر">
                    <svg class="theme-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3.5"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    <svg class="theme-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 15.5A8.5 8.5 0 0 1 8.5 3.8 8.5 8.5 0 1 0 20.2 15.5Z"/></svg>
                </button>
                @auth
                    @php($unread = \App\Models\UserNotification::query()->where('user_id', auth()->id())->whereNull('read_at')->count())
                    <details class="account-menu">
                        <summary class="account-summary" aria-label="قائمة حسابك{{ $unread ? '، '.$unread.' إشعارات جديدة' : '' }}">
                            <span class="account-initial" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="account-name">{{ auth()->user()->name }}</span>
                            @if ($unread)<span class="account-badge" aria-hidden="true">{{ $unread > 9 ? '9+' : $unread }}</span>@endif
                        </summary>
                        <nav class="account-panel" aria-label="حسابي">
                            <p class="account-panel-name">{{ auth()->user()->name }}</p>
                            <a href="{{ route('profile.show', auth()->user()) }}">ملفي واقتباساتي</a>
                            <a href="{{ route('quotes.create') }}">＋ أضف اقتباسًا</a>
                            <a href="{{ route('favorites') }}">المحفوظات</a>
                            <a href="{{ route('notifications') }}">الإشعارات @if ($unread)<b>{{ $unread }}</b>@endif</a>
                            <a href="{{ route('profile.edit') }}">تعديل الملف</a>
                            @if (auth()->user()->is_admin)<a href="{{ route('admin.index') }}">لوحة الإدارة</a>@endif
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="account-logout" type="submit">تسجيل الخروج</button></form>
                        </nav>
                    </details>
                @else
                    <a class="text-button" href="{{ route('login') }}">دخول</a>
                    <a class="button button-dark button-small" href="{{ route('register') }}">أنشئ حسابًا</a>
                @endauth
            </div>
        </header>
