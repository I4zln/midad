{{-- Homepage greeting written "in ink". Final state is the default; app.js / the inline script add .is-animating once per session. --}}
<div class="ink-welcome" data-ink-welcome>
    <h1 class="ink-welcome-title">
        @auth
            <span class="ink-line">أهلًا بك يا <span class="ink-name">{{ auth()->user()->name }}</span></span>
            <span class="ink-line ink-line-second">في مِـداد</span>
        @else
            <span class="ink-line">أهلًا بك في مِـداد</span>
        @endauth
    </h1>
    <div class="ink-stroke-wrap">
        <svg class="ink-stroke" viewBox="0 0 420 18" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M416 9.5C352 4.8 289 13.6 222 10.4C160 7.5 101 5.2 46 9.8C30 11.1 16 12.6 4 11.2" pathLength="1" />
        </svg>
        {{-- Rides the stroke while it is drawn; only rendered visibly during .is-animating. --}}
        <span class="ink-pen-track" aria-hidden="true"><x-fountain-pen class="ink-pen" /></span>
    </div>
    <p class="ink-welcome-line">هنا يهدأُ الحبرُ على الورق، وتبقى الكلمةُ أثرًا لا يجفّ.</p>
</div>
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    try {
        if (!sessionStorage.getItem('midad-ink-played') && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.currentScript.previousElementSibling.classList.add('is-animating');
            sessionStorage.setItem('midad-ink-played', '1');
        }
    } catch (e) {}
</script>
