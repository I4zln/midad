const root = document.documentElement;
const toast = document.querySelector('#toast');
const audioToggle = document.querySelector('#ethraa-audio-toggle');
const audioMute = document.querySelector('#ethraa-audio-mute');
const audioVolume = document.querySelector('#ethraa-audio-volume');
const audioChoice = document.querySelector('#ethraa-audio-choice');
const audio = document.querySelector('#ethraa-audio');
let toastTimeout;

function showToast(message, isError = /^تعذّر|انتهت/.test(message || '')) {
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.toggle('is-error', isError);
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.classList.add('is-visible');
    window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
}

// Theme: light / dark / follow the system (default). The inline script in the layout applies it before first paint.
const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
const themeLabels = { light: 'فاتح', dark: 'داكن', system: 'حسب النظام' };
function savedThemeChoice() {
    try {
        const choice = window.localStorage.getItem('athar-theme');
        return choice === 'light' || choice === 'dark' ? choice : 'system';
    } catch (error) { return 'system'; }
}
function applyTheme(choice, fade = false) {
    if (fade && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        root.classList.add('theme-changing');
        window.setTimeout(() => root.classList.remove('theme-changing'), 350);
    }
    root.dataset.theme = choice === 'system' ? (systemDark.matches ? 'dark' : 'light') : choice;
    root.dataset.themeChoice = choice;
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', getComputedStyle(root).getPropertyValue('--paper').trim());
    document.querySelectorAll('[data-theme-option]').forEach((option) => option.setAttribute('aria-pressed', String(option.dataset.themeOption === choice)));
    document.querySelectorAll('[data-theme-current]').forEach((label) => { label.textContent = themeLabels[choice]; });
}
applyTheme(savedThemeChoice());
systemDark.addEventListener('change', () => { if (savedThemeChoice() === 'system') applyTheme('system', true); });
document.addEventListener('midad:page-loaded', () => applyTheme(savedThemeChoice()));
document.addEventListener('click', (event) => {
    const option = event.target.closest('[data-theme-option]');
    if (!option) return;
    const choice = option.dataset.themeOption;
    try {
        if (choice === 'system') window.localStorage.removeItem('athar-theme');
        else window.localStorage.setItem('athar-theme', choice);
    } catch (error) {
        console.error('Unable to save the selected theme.', error);
        showToast('تعذّر حفظ اختيار المظهر لهذا المتصفح.');
    }
    applyTheme(choice, true);
    const menu = option.closest('details');
    if (menu) { menu.open = false; menu.querySelector('summary')?.focus(); }
});

if (audio && audioChoice && audioToggle) {
    const choiceKey = 'ethraa-audio-choice';
    const playingKey = 'ethraa-audio-playing';
    const volumeKey = 'ethraa-audio-volume';
    const muteKey = 'ethraa-audio-muted';
    let selectedTrack = '';
    let leavingPage = false;

    const readStorage = (key) => {
        try { return window.localStorage.getItem(key); } catch (error) { return null; }
    };
    const writeStorage = (key, value) => {
        try { window.localStorage.setItem(key, value); } catch (error) { /* Audio still works without storage. */ }
    };
    const trackOption = (key) => audioChoice.querySelector(`option[value="${key}"]`);
    const trackName = (key) => trackOption(key)?.textContent?.trim() || 'الصوت المختار';
    const savedVolume = Number(readStorage(volumeKey));
    audio.volume = Number.isFinite(savedVolume) && readStorage(volumeKey) !== null
        ? Math.min(0.18, Math.max(0, savedVolume))
        : 0.09;
    audio.muted = readStorage(muteKey) === 'true';
    audio.loop = true;
    audioVolume.value = String(audio.volume);
    const initialVolume = audio.volume;
    const initiallyMuted = audio.muted;

    const updatePlayer = (isPlaying = !audio.paused) => {
        audioToggle.disabled = !selectedTrack;
        audioMute.disabled = !selectedTrack;
        audioVolume.disabled = !selectedTrack;
        audioToggle.setAttribute('aria-pressed', String(isPlaying));
        audioToggle.setAttribute('aria-label', `${isPlaying ? 'إيقاف' : 'تشغيل'} ${trackName(selectedTrack)}`);
        audioToggle.title = `${isPlaying ? 'إيقاف' : 'تشغيل'} ${trackName(selectedTrack)}`;
        audioToggle.querySelector('span').textContent = isPlaying ? 'إيقاف' : 'تشغيل';
        if (audioMute) {
            audioMute.setAttribute('aria-pressed', String(audio.muted));
            audioMute.setAttribute('aria-label', audio.muted ? 'إلغاء كتم الصوت' : 'كتم الصوت');
            audioMute.textContent = audio.muted ? '×' : '♪';
        }
    };

    const setAudioChoice = (key, shouldPlay = true) => {
        const option = trackOption(key);
        if (!option) return false;
        audioChoice.value = key;
        selectedTrack = key === 'silent' || key === '' ? '' : key;
        writeStorage(choiceKey, selectedTrack ? key : 'silent');
        if (!selectedTrack) {
            audioChoice.value = 'silent';
            audio.pause();
            audio.removeAttribute('src');
            audio.load();
            writeStorage(playingKey, 'false');
            updatePlayer(false);
            return true;
        }

        const source = option.dataset.src;
        if (source && audio.src !== new URL(source, window.location.href).href) {
            audio.src = source;
            audio.load();
        }
        audio.volume = selectedTrack === 'islamic' ? 0.075 : 0.09;
        audioVolume.value = String(audio.volume);
        audio.muted = false;
        writeStorage(volumeKey, String(audio.volume));
        writeStorage(muteKey, 'false');
        updatePlayer(false);
        if (shouldPlay) {
            audio.play().then(() => {
                writeStorage(playingKey, 'true');
                updatePlayer(true);
            }).catch(() => {
                writeStorage(playingKey, 'false');
                updatePlayer(false);
                showToast('تعذّر تشغيل الملف الصوتي. جرّب زر التشغيل مرة أخرى.');
            });
        }
        return true;
    };

    const savedChoice = readStorage(choiceKey);
    if (savedChoice && trackOption(savedChoice)) {
        setAudioChoice(savedChoice, false);
        if (savedChoice !== 'silent') {
            audio.volume = initialVolume;
            audio.muted = initiallyMuted;
            audioVolume.value = String(initialVolume);
            writeStorage(volumeKey, String(initialVolume));
            writeStorage(muteKey, String(initiallyMuted));
        }
    } else updatePlayer(false);

    audioChoice.addEventListener('change', () => setAudioChoice(audioChoice.value, Boolean(audioChoice.value) && audioChoice.value !== 'silent'));
    audioToggle.addEventListener('click', () => {
        if (!selectedTrack) return;
        if (audio.paused) {
            audio.play().then(() => {
                writeStorage(playingKey, 'true');
                updatePlayer(true);
            }).catch(() => showToast('تعذّر تشغيل الصوت. حاول مرة أخرى.'));
        } else {
            audio.pause();
        }
    });
    audioMute?.addEventListener('click', () => {
        audio.muted = !audio.muted;
        writeStorage(muteKey, String(audio.muted));
        updatePlayer();
    });
    audioVolume?.addEventListener('input', () => {
        audio.volume = Number(audioVolume.value);
        if (audio.volume > 0) audio.muted = false;
        writeStorage(volumeKey, String(audio.volume));
        writeStorage(muteKey, String(audio.muted));
        updatePlayer();
    });
    audio.addEventListener('play', () => updatePlayer(true));
    audio.addEventListener('pause', () => {
        updatePlayer(false);
        if (!leavingPage) writeStorage(playingKey, 'false');
    });
    window.addEventListener('beforeunload', () => {
        leavingPage = true;
        writeStorage(playingKey, String(Boolean(selectedTrack) && !audio.paused));
    });

    // Never autoplay: a track that was playing resumes only after the visitor's first interaction.
    if (selectedTrack && readStorage(playingKey) === 'true') {
        const resumeAfterNavigation = () => {
            if (!audio.paused || !selectedTrack) return;
            audio.play().then(() => updatePlayer(true)).catch(() => writeStorage(playingKey, 'false'));
            document.removeEventListener('pointerdown', resumeAfterNavigation);
            document.removeEventListener('keydown', resumeAfterNavigation);
        };
        document.addEventListener('pointerdown', resumeAfterNavigation, { once: true });
        document.addEventListener('keydown', resumeAfterNavigation, { once: true });
    }
}

document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-preview]');
    if (!input) return;
    const image = input.files?.[0];
    const preview = document.getElementById(input.dataset.preview);
    if (!image || !preview || !image.type.startsWith('image/')) return;
    preview.style.backgroundImage = `url("${URL.createObjectURL(image)}")`;
    preview.classList.add('has-preview');
});

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-toggle-password]');
    if (!toggle) return;
    const input = document.getElementById(toggle.dataset.togglePassword);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.textContent = show ? 'إخفاء' : 'إظهار';
    toggle.setAttribute('aria-pressed', String(show));
    toggle.setAttribute('aria-label', `${show ? 'إخفاء' : 'إظهار'} كلمة المرور`);
    input.focus({ preventScroll: true });
});

document.addEventListener('click', (event) => {
    const card = event.target.closest('[data-poet-reveal]');
    if (!card) return;
    const opening = !card.classList.contains('is-revealed');
    document.querySelectorAll('[data-poet-reveal].is-revealed').forEach((openCard) => {
        openCard.classList.remove('is-revealed');
        openCard.setAttribute('aria-expanded', 'false');
        openCard.querySelectorAll('.poet-verse-panel').forEach((panel) => panel.setAttribute('aria-hidden', 'true'));
    });
    if (opening) {
        card.classList.add('is-revealed');
        card.setAttribute('aria-expanded', 'true');
        card.querySelectorAll('.poet-verse-panel').forEach((panel) => panel.setAttribute('aria-hidden', 'false'));
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('[data-poet-reveal].is-revealed').forEach((card) => {
        card.classList.remove('is-revealed');
        card.setAttribute('aria-expanded', 'false');
        card.querySelectorAll('.poet-verse-panel').forEach((panel) => panel.setAttribute('aria-hidden', 'true'));
    });
});


let composerDialog = document.querySelector('#composer-dialog');
document.addEventListener('click', (event) => {
    if (event.target.closest('[data-open-composer]')) document.querySelector('#composer-dialog')?.showModal();
    if (event.target.closest('[data-close-dialog]')) event.target.closest('[data-close-dialog]').closest('dialog')?.close();
    const copyButton = event.target.closest('[data-copy-link]');
    if (copyButton) navigator.clipboard.writeText(window.location.href).then(() => showToast('نُسخ رابط الصفحة.'), () => showToast('تعذّر نسخ الرابط. انسخه من شريط العنوان.'));

    const quoteCopyButton = event.target.closest('[data-copy-text]');
    if (quoteCopyButton) navigator.clipboard.writeText(quoteCopyButton.dataset.copyText).then(() => showToast('نُسخ نص الاقتباس.'), () => showToast('تعذّر نسخ الاقتباس على هذا الجهاز.'));

    const shareButton = event.target.closest('[data-share-url]');
    if (shareButton) {
        const shareData = { title: shareButton.dataset.shareTitle, text: shareButton.dataset.shareText, url: new URL(shareButton.dataset.shareUrl, window.location.origin).href };
        if (navigator.share) navigator.share(shareData).catch(() => {});
        else navigator.clipboard.writeText(`${shareData.text}\n${shareData.url}`).then(() => showToast('نُسخ الاقتباس ورابطه للمشاركة.'), () => showToast('تعذّرت المشاركة على هذا الجهاز.'));
    }
});

document.addEventListener('input', (event) => {
    const textarea = event.target.closest('[data-char-counter]');
    if (!textarea) return;
    const counter = textarea.closest('.field-label')?.querySelector('[data-character-count]')
        || textarea.parentElement?.querySelector('[data-character-count]');
    if (counter) counter.textContent = `${Array.from(textarea.value).length} / ${textarea.maxLength}`;
});

document.addEventListener('keydown', (event) => {
    if (event.key !== '/' || ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
    const searchInput = document.querySelector('#quote-search');
    if (!searchInput) return;
    event.preventDefault();
    searchInput.focus();
});



function wrapCanvasText(context, text, maxWidth) {
    const lines = [];
    let line = '';

    for (const word of text.split(/\s+/)) {
        const candidate = line ? `${line} ${word}` : word;
        if (line && context.measureText(candidate).width > maxWidth) {
            lines.push(line);
            line = word;
        } else {
            line = candidate;
        }
    }
    if (line) lines.push(line);
    return lines;
}

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-export-quote]');
    if (button) exportQuote(button);
});

async function exportQuote(button) {
        await document.fonts.ready;
        const quote = button.dataset;
        const canvas = document.createElement('canvas');
        canvas.width = 1200;
        canvas.height = 1500;
        let context = canvas.getContext('2d');
        if (!context) {
            showToast('تعذّر تجهيز الصورة على هذا الجهاز.');
            return;
        }
        context.font = '500 54px "Thmanyah Serif Text", serif';
        const lines = wrapCanvasText(context, quote.text, 850);
        canvas.height = Math.max(1500, 920 + lines.length * 100);
        context = canvas.getContext('2d');
        if (!context) {
            showToast('تعذّر تجهيز الصورة على هذا الجهاز.');
            return;
        }
        context.fillStyle = '#f5f2eb';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.strokeStyle = '#d8d0c4';
        context.lineWidth = 2;
        context.strokeRect(46, 46, canvas.width - 92, canvas.height - 92);
        context.direction = 'rtl';
        context.textAlign = 'right';
        context.fillStyle = '#a66e48';
        context.font = '600 27px "Thmanyah Sans", sans-serif';
        context.fillText('MIDAD  |  مِداد', 1080, 125);
        context.textAlign = 'center';
        context.fillStyle = '#c3a485';
        context.font = '100px Georgia, serif';
        context.fillText('“', 600, 380);
        context.fillStyle = '#24231f';
        context.font = '500 54px "Thmanyah Serif Text", serif';
        lines.forEach((line, index) => context.fillText(line, 600, 520 + index * 100));
        const bylineY = 520 + lines.length * 100 + 90;
        context.strokeStyle = '#cdbba8';
        context.beginPath();
        context.moveTo(360, bylineY - 45);
        context.lineTo(840, bylineY - 45);
        context.stroke();
        context.fillStyle = '#24231f';
        context.font = '500 28px "Thmanyah Serif Text", serif';
        context.fillText(quote.author, 600, bylineY);
        context.fillStyle = '#817b70';
        context.font = '400 23px "Thmanyah Serif Text", serif';
        context.fillText(quote.source, 600, bylineY + 44);
        context.direction = 'ltr';
        context.fillStyle = '#a66e48';
        context.font = '600 18px "Thmanyah Sans", sans-serif';
        context.fillText('WORDS THAT STAY', 600, canvas.height - 120);
        const link = document.createElement('a');
        link.download = 'midad-quote.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        showToast('تم تجهيز صورة الاقتباس.');
}

// Pages fetched on hover/touch, reused if the click follows within a few seconds.
const prefetched = new Map();
const PREFETCH_TTL = 10000;

function fetchPage(url) {
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(async (response) => ({
        ok: response.ok && Boolean(response.headers.get('content-type')?.includes('text/html')),
        html: await response.text(),
    }));
}

function prefetchPage(url) {
    const cached = prefetched.get(url);
    if (cached && Date.now() - cached.at < PREFETCH_TTL) return;
    prefetched.set(url, { at: Date.now(), page: fetchPage(url).catch(() => null) });
}

function setNavigating(active) {
    root.classList.toggle('is-navigating', active);
    if (active) document.querySelectorAll('.ambient-menu[open], .account-menu[open], .theme-menu[open]').forEach((menu) => { menu.open = false; });
}

async function loadPage(url, push = true) {
    const source = new URL(window.location.href);
    const previousScrollY = window.scrollY;
    if (push) history.replaceState({ ...history.state, scrollY: previousScrollY }, '', source.href);
    setNavigating(true);
    try {
        const cached = prefetched.get(url);
        prefetched.clear();
        const page = (cached && Date.now() - cached.at < PREFETCH_TTL && await cached.page) || await fetchPage(url);
        if (!page.ok) { window.location.href = url; return; }
        const nextDocument = new DOMParser().parseFromString(page.html, 'text/html');
        const currentShell = document.querySelector('.site-shell');
        const nextShell = nextDocument.querySelector('.site-shell');
        if (!currentShell || !nextShell) { window.location.href = url; return; }
        const currentTopbar = currentShell.querySelector('.topbar');
        const nextTopbar = nextShell.querySelector('.topbar');
        if (currentTopbar && nextTopbar) {
            currentTopbar.querySelectorAll('.main-nav a').forEach((link) => {
                const incoming = [...nextTopbar.querySelectorAll('.main-nav a')].find((candidate) => candidate.getAttribute('href') === link.getAttribute('href'));
                link.classList.toggle('is-active', incoming?.classList.contains('is-active') ?? false);
            });
        }
        [...currentShell.children].filter((child) => child !== currentTopbar).forEach((child) => child.remove());
        [...nextShell.children].filter((child) => !child.classList.contains('topbar')).forEach((child) => currentShell.append(child));
        const nextDialog = nextDocument.querySelector('#composer-dialog');
        document.querySelector('#composer-dialog')?.remove();
        if (nextDialog) document.body.append(nextDialog);
        composerDialog = nextDialog;
        document.title = nextDocument.title;
        ['csrf-token', 'flash-status'].forEach((name) => {
            const incoming = nextDocument.querySelector(`meta[name="${name}"]`);
            if (incoming) document.querySelector(`meta[name="${name}"]`)?.setAttribute('content', incoming.content);
        });
        const target = new URL(url, window.location.href);
        if (push) history.pushState({ scrollY: source.pathname === target.pathname ? previousScrollY : 0 }, '', url);
        if (target.hash) document.querySelector(target.hash)?.scrollIntoView();
        else if (!push && Number.isFinite(history.state?.scrollY)) window.scrollTo({ top: history.state.scrollY, behavior: 'instant' });
        else if (source.pathname === target.pathname && source.search !== target.search) window.scrollTo({ top: previousScrollY, behavior: 'instant' });
        else window.scrollTo({ top: 0, behavior: 'instant' });
        const status = nextDocument.querySelector('meta[name="flash-status"]')?.content;
        if (status) showToast(status);
        if (nextDialog?.dataset.quoteFormErrors === 'true') nextDialog.showModal();
        markClampedQuotes();
        document.dispatchEvent(new CustomEvent('midad:page-loaded'));
    } catch (error) { window.location.href = url; } finally { setNavigating(false); }
}

// Show "قراءة المزيد" only on cards whose text is actually cut by the CSS line clamp.
function markClampedQuotes() {
    document.querySelectorAll('.quote-card .card-quote-text').forEach((text) => {
        text.closest('.quote-card')?.classList.toggle('is-unclamped', text.scrollHeight <= text.clientHeight + 1);
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-like-form]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const count = button?.querySelector('b');
    if (!button || !count || button.disabled) return;

    const wasLiked = button.getAttribute('aria-pressed') === 'true';
    const previousCount = Number.parseInt(count.textContent, 10) || 0;
    const render = (liked, likesCount) => {
        button.classList.toggle('is-liked', liked);
        button.setAttribute('aria-pressed', String(liked));
        button.setAttribute('aria-label', liked ? 'إزالة الإعجاب' : 'أعجبني');
        count.textContent = String(likesCount);
    };

    button.disabled = true;
    button.classList.add('is-loading');
    render(!wasLiked, Math.max(0, previousCount + (wasLiked ? -1 : 1)));

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (!response.ok) throw new Error(String(response.status));
        const result = await response.json();
        render(result.liked, result.likes_count);
        button.classList.add('like-just-changed');
        window.setTimeout(() => button.classList.remove('like-just-changed'), 420);
        showToast(result.message);
    } catch (error) {
        render(wasLiked, previousCount);
        showToast(error.message === '401'
            ? 'انتهت جلستك. سجّل الدخول ثم حاول الإعجاب من جديد.'
            : 'تعذّر تحديث الإعجاب. تحقق من الاتصال ثم حاول مرة أخرى.');
    } finally {
        button.disabled = false;
        button.classList.remove('is-loading');
    }
});

// Same-origin page links handled by loadPage(); null for anything the browser should handle itself.
function inPageDestination(link) {
    if (!link || link.target || link.hasAttribute('download') || link.closest('form')) return null;
    const destination = new URL(link.href, window.location.href);
    if (destination.origin !== window.location.origin) return null;
    if (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash) return null;
    return destination;
}

document.addEventListener('click', (event) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const destination = inPageDestination(event.target.closest('a[href]'));
    if (!destination) return;
    event.preventDefault();
    loadPage(destination.href);
});
['pointerover', 'touchstart', 'focusin'].forEach((type) => document.addEventListener(type, (event) => {
    const destination = inPageDestination(event.target.closest?.('a[href]'));
    if (destination && destination.href !== window.location.href) prefetchPage(destination.href);
}, { passive: true }));

// Full-page POST forms: show progress and ignore repeat submits until the next page arrives.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (event.defaultPrevented || !(form instanceof HTMLFormElement) || ['get', 'dialog'].includes(form.method.toLowerCase())) return;
    if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => button.classList.add('is-loading'));
    prefetched.clear();
    setNavigating(true);
});
window.addEventListener('pageshow', () => {
    setNavigating(false);
    document.querySelectorAll('form[data-submitting]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('.is-loading').forEach((button) => button.classList.remove('is-loading'));
    });
});
window.addEventListener('popstate', () => loadPage(window.location.href, false));
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'get' || form.target) return;
    const destination = new URL(form.action || window.location.href, window.location.href);
    if (destination.origin !== window.location.origin || form.querySelector('input[type="file"]')) return;
    event.preventDefault();
    const parameters = new URLSearchParams(new FormData(form));
    destination.search = parameters.toString();
    loadPage(destination.href);
});
const flashStatus = document.querySelector('meta[name="flash-status"]')?.content;
if (flashStatus) showToast(flashStatus);
if (composerDialog && document.querySelector('[data-quote-form-errors="true"]')) composerDialog.showModal();
markClampedQuotes();
document.fonts?.ready.then(markClampedQuotes);
let clampResizeTimer;
window.addEventListener('resize', () => {
    window.clearTimeout(clampResizeTimer);
    clampResizeTimer = window.setTimeout(markClampedQuotes, 150);
});







// Close the navbar audio menu on outside click or Escape.
document.addEventListener('click', (event) => {
    document.querySelectorAll('.ambient-menu[open], .account-menu[open], .theme-menu[open]').forEach((menu) => { if (!menu.contains(event.target)) menu.open = false; });
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.ambient-menu[open], .account-menu[open], .theme-menu[open]').forEach((menu) => { menu.open = false; menu.querySelector('summary')?.focus(); });
});

// In-page navigation inserts the homepage without running its inline script; apply the same once-per-session rule.
document.addEventListener('midad:page-loaded', () => {
    const welcome = document.querySelector('[data-ink-welcome]');
    if (!welcome) return;
    try {
        if (sessionStorage.getItem('midad-ink-played') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        welcome.classList.add('is-animating');
        sessionStorage.setItem('midad-ink-played', '1');
    } catch (error) { /* Without storage the greeting simply shows in its final state. */ }
});

// Inline Arabic validation for forms marked data-validate (server errors use the same .field-error slot).
function fieldProblem(input) {
    const length = Array.from(input.value).length;
    if (input.dataset.match) {
        const other = document.getElementById(input.dataset.match);
        input.setCustomValidity(other && input.value && input.value !== other.value ? 'mismatch' : '');
    }
    const { validity } = input;
    if (validity.valid) return '';
    if (validity.valueMissing) return 'هذا الحقل مطلوب.';
    if (validity.typeMismatch && input.type === 'email') return 'أدخل بريدًا إلكترونيًا صحيحًا، مثل name@example.com.';
    if (validity.tooShort) return `عدد الأحرف يجب ألا يقل عن ${input.minLength} (الحالي ${length}).`;
    if (validity.tooLong) return `عدد الأحرف يجب ألا يزيد على ${input.maxLength}.`;
    if (validity.customError) return 'تأكيد كلمة المرور لا يطابق كلمة المرور.';
    return 'تحقّق من قيمة هذا الحقل.';
}

function showFieldProblem(input, message) {
    const label = input.closest('.field-label');
    if (!label) return;
    let error = label.querySelector('.field-error');
    if (message) {
        if (!error) {
            error = document.createElement('small');
            error.className = 'field-error';
            error.id = `${input.id}-error`;
            error.setAttribute('role', 'alert');
            label.append(error);
        }
        error.textContent = message;
    } else error?.remove();
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    input.setAttribute('aria-describedby', [`${input.id}-hint`, message ? `${input.id}-error` : ''].filter((id) => id && document.getElementById(id)).join(' '));
}

function linkFieldDescriptions(scope = document) {
    scope.querySelectorAll('.field-label input[id], .field-label textarea[id], .field-label select[id]').forEach((input) => {
        const hasError = Boolean(document.getElementById(`${input.id}-error`));
        input.setAttribute('aria-invalid', hasError ? 'true' : 'false');
        const ids = [`${input.id}-hint`, `${input.id}-error`].filter((id) => document.getElementById(id));
        if (ids.length) input.setAttribute('aria-describedby', ids.join(' '));
    });
}

const validatedField = (target) => target?.closest?.('form[data-validate]') && target.matches?.('input:not([type="hidden"]):not([type="checkbox"]), textarea, select') ? target : null;
document.addEventListener('focusout', (event) => {
    const input = validatedField(event.target);
    if (!input || (!input.value && !input.dataset.touched)) return;
    input.dataset.touched = 'true';
    showFieldProblem(input, fieldProblem(input));
});
document.addEventListener('input', (event) => {
    const input = validatedField(event.target);
    if (!input) return;
    if (input.dataset.touched || input.getAttribute('aria-invalid') === 'true') showFieldProblem(input, fieldProblem(input));
    const confirmation = input.form.querySelector(`[data-match="${input.id}"]`);
    if (confirmation?.dataset.touched) showFieldProblem(confirmation, fieldProblem(confirmation));
});
document.addEventListener('invalid', (event) => {
    const input = validatedField(event.target);
    if (!input) return;
    event.preventDefault();
    input.dataset.touched = 'true';
    showFieldProblem(input, fieldProblem(input));
    const firstInvalid = input.form.querySelector(':invalid');
    if (firstInvalid === input) input.focus();
}, true);
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-validate]')) return;
    form.querySelectorAll('[data-match]').forEach((input) => fieldProblem(input));
    if (!form.checkValidity()) { event.preventDefault(); event.stopImmediatePropagation(); form.reportValidity(); }
}, true);
linkFieldDescriptions();
document.addEventListener('midad:page-loaded', () => linkFieldDescriptions());

// Forms with data-confirm ask first in the shared dialog (components/confirm-dialog.blade.php).
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
    if (form.dataset.confirmed === 'true') { delete form.dataset.confirmed; return; }
    event.preventDefault();
    event.stopImmediatePropagation();
    const dialog = document.querySelector('#confirm-dialog');
    if (!dialog?.showModal) { if (window.confirm(form.dataset.confirm)) { form.dataset.confirmed = 'true'; form.requestSubmit(); } return; }
    dialog.querySelector('#confirm-dialog-message').textContent = form.dataset.confirm;
    dialog.addEventListener('close', () => {
        if (dialog.returnValue !== 'confirm') return;
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    }, { once: true });
    dialog.returnValue = '';
    dialog.showModal();
}, true);

// «من نحن» page: ink reveal once per visit, timeline drawn by scroll, skill notes, journey items open on hover.
function initAboutPage() {
    const page = document.querySelector('[data-about-page]');
    if (!page || page.dataset.ready) return;
    page.dataset.ready = 'true';

    const output = page.querySelector('[data-skill-output]');
    const skills = [...page.querySelectorAll('[data-skill-note]')];
    const showNote = (button) => {
        output.textContent = button ? document.getElementById(button.dataset.skillNote).textContent : output.dataset.default;
        output.classList.toggle('is-active', Boolean(button));
    };
    const pressedSkill = () => skills.find((skill) => skill.getAttribute('aria-pressed') === 'true');
    skills.forEach((skill) => {
        skill.addEventListener('click', () => {
            const pressed = skill.getAttribute('aria-pressed') !== 'true';
            skills.forEach((other) => other.setAttribute('aria-pressed', String(other === skill && pressed)));
            showNote(pressedSkill());
        });
        skill.addEventListener('mouseenter', () => showNote(skill));
        skill.addEventListener('focus', () => showNote(skill));
        skill.addEventListener('mouseleave', () => showNote(pressedSkill()));
        skill.addEventListener('blur', () => showNote(pressedSkill()));
    });

    // With a mouse, hovering an item opens it; clicking while hover-opened keeps it open.
    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        page.querySelectorAll('.us-journey details').forEach((details) => {
            details.addEventListener('mouseenter', () => { if (!details.open) { details.open = true; details.dataset.hoverOpen = 'true'; } });
            details.addEventListener('mouseleave', () => { if (details.dataset.hoverOpen) { details.open = false; delete details.dataset.hoverOpen; } });
            details.querySelector('summary').addEventListener('click', (event) => {
                if (!details.dataset.hoverOpen) return;
                event.preventDefault();
                delete details.dataset.hoverOpen;
            });
        });
    }

    let animate = !window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window;
    try {
        if (sessionStorage.getItem('midad-about-played')) animate = false;
        else if (animate) sessionStorage.setItem('midad-about-played', '1');
    } catch (error) { animate = false; }
    if (!animate) return;

    page.classList.add('is-revealing');
    const observer = new IntersectionObserver((entries) => entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-seen');
        observer.unobserve(entry.target);
    }), { rootMargin: '0px 0px -12% 0px' });
    page.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));

    const journey = page.querySelector('[data-journey]');
    const items = [...journey.querySelectorAll('.us-journey-item')];
    let frame = 0;
    let drawn = 0; // the line only grows, so scrolling back up never erases it
    const draw = () => {
        frame = 0;
        if (!journey.isConnected) { window.removeEventListener('scroll', onScroll); return; }
        const box = journey.getBoundingClientRect();
        const reach = window.innerHeight * 0.8 - box.top;
        drawn = Math.max(drawn, Math.min(1, reach / box.height));
        journey.style.setProperty('--draw', drawn.toFixed(3));
        items.forEach((item) => { if (item.offsetTop + 18 <= drawn * box.height) item.classList.add('is-reached'); });
        if (drawn === 1) window.removeEventListener('scroll', onScroll);
    };
    const onScroll = () => { if (!frame) frame = requestAnimationFrame(draw); };
    window.addEventListener('scroll', onScroll, { passive: true });
    draw();
}
initAboutPage();
document.addEventListener('midad:page-loaded', initAboutPage);

// Saving to «المحفوظات» updates in place like likes: optimistic toggle, server confirms, rolled back on failure.
document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-favorite-form]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    if (!button || button.disabled) return;

    const wasSaved = button.classList.contains('is-favorited');
    const render = (saved) => {
        button.classList.toggle('is-favorited', saved);
        button.setAttribute('aria-pressed', String(saved));
        if (button.classList.contains('icon-button')) {
            const label = saved ? 'إزالة من المحفوظات' : 'حفظ الاقتباس';
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
        } else {
            button.textContent = saved ? 'محفوظ ✓ إزالة من المحفوظات' : 'حفظ في المحفوظات';
        }
    };

    button.disabled = true;
    render(!wasSaved);
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (!response.ok) throw new Error(String(response.status));
        const result = await response.json();
        render(result.saved);
        showToast(result.message);
        // On the saved list, an unsaved quote leaves the list.
        const onSavedList = window.location.pathname === '/favorites' || new URLSearchParams(window.location.search).get('tab') === 'saved';
        const card = form.closest('.quote-card');
        if (onSavedList && !result.saved && card) {
            card.style.transition = 'opacity .25s ease';
            card.style.opacity = '0';
            window.setTimeout(() => card.remove(), 260);
        }
    } catch (error) {
        render(wasSaved);
        showToast(error.message === '401'
            ? 'انتهت جلستك. سجّل الدخول ثم حاول الحفظ من جديد.'
            : 'تعذّر تحديث المحفوظات. تحقق من الاتصال ثم حاول مرة أخرى.');
    } finally {
        button.disabled = false;
    }
}, true); // capture: runs before the full-page form guard below marks the form as submitting
