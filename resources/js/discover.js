// «اكتشف» (resources/views/discover/index.blade.php): batches that load before the reader reaches the end,
// two views («تأمّل» one quote per screen, «الخط الزمني» a feed of cards), keyboard steps, a sign-in prompt for guests,
// and the way back to the same place after opening a quote. Likes, saves, copy and share use the shared handlers in app.js.
const MODE_KEY = 'midad-discover-mode';
const STATE_PREFIX = 'midad-discover:';
const STATE_TTL = 60 * 60 * 1000;
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const storage = {
    get(store, key) { try { return window[store].getItem(key); } catch (error) { return null; } },
    set(store, key, value) { try { window[store].setItem(key, value); return true; } catch (error) { return false; } },
};

export function initDiscover({ markClampedQuotes }, { restored = false } = {}) {
    const page = document.querySelector('[data-discover]');
    // «اكتشف» places itself (a reload starts at the top; Back restores below); other pages keep the browser's behaviour.
    if ('scrollRestoration' in history) history.scrollRestoration = page ? 'manual' : 'auto';
    if (!page || page.dataset.ready) return;
    page.dataset.ready = 'true';

    const stream = page.querySelector('[data-discover-stream]');
    const sentinel = page.querySelector('[data-discover-sentinel]');
    const shown = new Set();
    let next = page.dataset.next || null;
    let inFlight = null;
    let emptyBatches = 0;
    let observer = null;

    const items = () => [...stream.querySelectorAll('.discover-item')];
    const isFocus = () => page.dataset.mode === 'focus';
    const setStatus = (status) => {
        sentinel.dataset.status = status;
        stream.setAttribute('aria-busy', String(status === 'loading'));
    };
    const number = () => items().forEach((item, index) => item.setAttribute('aria-posinset', String(index + 1)));
    items().forEach((item) => shown.add(item.dataset.quoteId));
    number();

    // «تأمّل» fills the screen below the bars (and above the phone's bottom navigation).
    const sizeStream = () => {
        const nav = document.querySelector('.main-nav');
        const bottom = nav && getComputedStyle(nav).position === 'fixed' ? nav.offsetHeight : 0;
        const bar = page.querySelector('.discover-bar');
        const top = bar.getBoundingClientRect().bottom + window.scrollY;
        page.style.setProperty('--discover-stream-h', `${Math.max(380, window.innerHeight - top - bottom)}px`);
        page.style.setProperty('--discover-bar-h', `${bar.offsetHeight}px`);
    };

    const currentItem = () => {
        const list = items();
        if (isFocus()) return list.find((item) => item.offsetTop + item.offsetHeight / 2 > stream.scrollTop) || list[0];
        // Timeline: the last card whose top has reached the line just under the sticky bar.
        const line = timelineLine() + 24;
        return list.filter((item) => item.getBoundingClientRect().top <= line).pop() || list[0];
    };
    const timelineLine = () => page.querySelector('.discover-bar').getBoundingClientRect().height + 16;

    const reveal = (element, smooth = false) => {
        if (!element) return;
        const behavior = smooth && !reducedMotion() ? 'smooth' : 'instant';
        if (isFocus()) stream.scrollTo({ top: element.offsetTop, behavior });
        else window.scrollTo({ top: Math.max(0, element.getBoundingClientRect().top + window.scrollY - timelineLine()), behavior });
    };

    // Saved per history entry so Back returns to the same quote, with the batches already loaded.
    const stateKey = () => history.state?.discoverKey;
    // Layout position in the document (ignores the cards' entry animation, which moves them while it plays).
    const documentTop = (item) => stream.getBoundingClientRect().top + window.scrollY + item.offsetTop;
    const saveState = () => {
        if (!page.isConnected || !stateKey()) return;
        const anchor = currentItem();
        storage.set('sessionStorage', STATE_PREFIX + stateKey(), JSON.stringify({
            at: Date.now(), url: window.location.href, csrf: csrf(), mode: page.dataset.mode, next,
            status: sentinel.dataset.status === 'loading' ? 'idle' : sentinel.dataset.status,
            html: items().map((item) => item.outerHTML).join(''), streamTop: stream.scrollTop, windowY: window.scrollY,
            anchor: anchor?.dataset.quoteId, anchorTop: anchor ? documentTop(anchor) - window.scrollY : 0,
        }));
    };
    // Put the card that was on screen back at the same height (robust to small layout shifts while the page settles).
    const placeSaved = (saved) => {
        if (isFocus()) { stream.scrollTop = saved.streamTop; return; }
        const anchor = saved.anchor && stream.querySelector(`.discover-item[data-quote-id="${CSS.escape(saved.anchor)}"]`);
        const top = anchor ? documentTop(anchor) - saved.anchorTop : saved.windowY;
        window.scrollTo({ top, behavior: 'instant' });
    };
    let saveTimer;
    const saveSoon = () => { window.clearTimeout(saveTimer); saveTimer = window.setTimeout(saveState, 250); };

    const append = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html;
        let added = 0;
        template.content.querySelectorAll('.discover-item').forEach((item) => {
            if (shown.has(item.dataset.quoteId)) return; // never the same quote twice in one feed
            shown.add(item.dataset.quoteId);
            stream.insertBefore(item, sentinel);
            added += 1;
        });
        number();
        markClampedQuotes();
        return added;
    };

    const watch = () => {
        observer?.disconnect();
        if (!('IntersectionObserver' in window)) return;
        // Start the next request about three screens before the end, so it is usually there before the reader is.
        observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) load();
        }, { root: isFocus() ? stream : null, rootMargin: isFocus() ? '0px 0px 300% 0px' : '0px 0px 200% 0px' });
        observer.observe(sentinel);
    };

    async function load() {
        if (!next || inFlight || !page.isConnected) return;
        const cursor = next;
        const controller = new AbortController();
        inFlight = controller;
        setStatus('loading');
        try {
            const url = new URL(page.dataset.feedUrl, window.location.href);
            url.searchParams.set('cursor', cursor);
            const response = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
            if (response.status === 422) {
                next = null;
                setStatus('error');
                sentinel.dataset.restart = 'true';
                return;
            }
            if (!response.ok) throw new Error(String(response.status));
            const batch = await response.json();
            if (cursor !== next) return;
            const added = append(batch.html);
            next = batch.next || null;
            setStatus(next ? 'idle' : 'end');
            saveState();
            emptyBatches = added === 0 ? emptyBatches + 1 : 0;
            // Re-observing reports the sentinel again if it is still near, so a short batch keeps the feed going.
            if (next && emptyBatches < 5 && observer) { observer.unobserve(sentinel); observer.observe(sentinel); }
        } catch (error) {
            if (error.name !== 'AbortError') setStatus('error');
        } finally {
            if (inFlight === controller) inFlight = null;
        }
    }

    const applyMode = (mode, save = false) => {
        if (mode !== 'focus' && mode !== 'timeline') return;
        const keep = currentItem();
        page.dataset.mode = mode;
        page.querySelectorAll('[data-discover-mode]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.discoverMode === mode)));
        sizeStream();
        markClampedQuotes();
        watch();
        if (keep) reveal(keep);
        if (!save) return;
        storage.set('localStorage', MODE_KEY, mode);
        if (page.dataset.modeUrl) {
            fetch(page.dataset.modeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ mode }),
            }).catch(() => { /* Still remembered in this browser. */ });
        }
        saveState();
    };

    // Restore after Back (in-page or the browser's), never on a fresh visit or a reload.
    const navigation = performance.getEntriesByType?.('navigation')?.[0]?.type;
    let state = null;
    if (stateKey() && (restored || navigation === 'back_forward')) {
        try { state = JSON.parse(storage.get('sessionStorage', STATE_PREFIX + stateKey()) || 'null'); } catch (error) { state = null; }
        if (!state || state.url !== window.location.href || state.csrf !== csrf() || Date.now() - state.at > STATE_TTL) state = null;
    }
    if (!stateKey()) history.replaceState({ ...history.state, discoverKey: Math.random().toString(36).slice(2) }, '', window.location.href);
    try {
        Object.keys(sessionStorage).filter((key) => key.startsWith(STATE_PREFIX) && key !== STATE_PREFIX + stateKey())
            .forEach((key) => { if (Date.now() - (JSON.parse(sessionStorage.getItem(key))?.at || 0) > STATE_TTL) sessionStorage.removeItem(key); });
    } catch (error) { /* Nothing to tidy without storage. */ }

    if (!page.hasAttribute('data-mode-account')) {
        const saved = storage.get('localStorage', MODE_KEY);
        if (saved === 'focus' || saved === 'timeline') page.dataset.mode = saved;
    }
    if (state) {
        page.dataset.restored = 'true';
        items().forEach((item) => item.remove());
        shown.clear();
        append(state.html);
        next = state.next;
        setStatus(state.status);
        page.dataset.mode = state.mode;
    }
    applyMode(page.dataset.mode);
    if (state) {
        placeSaved(state);
        requestAnimationFrame(() => placeSaved(state));
        document.fonts?.ready.then(() => { if (page.isConnected) placeSaved(state); });
    }

    page.addEventListener('click', (event) => {
        const modeButton = event.target.closest('[data-discover-mode]');
        if (modeButton) { applyMode(modeButton.dataset.discoverMode, true); return; }

        if (event.target.closest('[data-discover-retry]')) {
            if (sentinel.dataset.restart) { window.location.reload(); return; }
            load();
            return;
        }

        // Guests: say why signing in is needed, then come back to this same quote.
        const guestLink = event.target.closest('[data-guest-action]');
        const dialog = page.querySelector('#discover-login');
        if (guestLink && dialog?.showModal) {
            event.preventDefault();
            const reason = dialog.querySelector('#discover-login-reason');
            reason.textContent = guestLink.dataset.guestAction === 'save' ? reason.dataset.reasonSave : reason.dataset.reasonLike;
            const login = new URL(guestLink.href, window.location.href);
            dialog.querySelector('[data-discover-login]').href = login.href;
            const register = dialog.querySelector('[data-discover-register]');
            const registerUrl = new URL(register.href, window.location.href);
            registerUrl.search = login.search;
            register.href = registerUrl.href;
            saveState();
            dialog.showModal();
            return;
        }

        if (event.target.closest('a[href]')) saveState();
    });

    // ↑ ↓ (in «تأمّل») and J / K (both views, any keyboard layout) step one quote.
    const onKey = (event) => {
        if (!page.isConnected) { document.removeEventListener('keydown', onKey); return; }
        if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (event.target.closest?.('input, textarea, select, [contenteditable="true"]') || document.querySelector('dialog[open], details[open]')) return;
        const arrow = isFocus() ? { ArrowDown: 1, ArrowUp: -1 }[event.key] : undefined;
        const step = arrow ?? { KeyJ: 1, KeyK: -1 }[event.code];
        if (!step) return;
        event.preventDefault();
        const list = items();
        const target = list[list.indexOf(currentItem()) + step];
        if (target) {
            reveal(target, true);
            target.focus({ preventScroll: true });
        } else if (step > 0) {
            reveal(sentinel, true);
            load();
        }
    };
    document.addEventListener('keydown', onKey);

    const onScroll = () => { if (!page.isConnected) { window.removeEventListener('scroll', onScroll); return; } saveSoon(); };
    stream.addEventListener('scroll', saveSoon, { passive: true });
    window.addEventListener('scroll', onScroll, { passive: true });
    const onResize = () => { if (!page.isConnected) { window.removeEventListener('resize', onResize); return; } sizeStream(); };
    window.addEventListener('resize', onResize);
    const onOnline = () => { if (!page.isConnected) { window.removeEventListener('online', onOnline); return; } if (sentinel.dataset.status === 'error' && !sentinel.dataset.restart) load(); };
    window.addEventListener('online', onOnline);
    window.addEventListener('pagehide', saveState, { once: true });
}
