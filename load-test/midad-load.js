// MIDAD load test (k6). Run ONLY against a local or staging copy, never the live site.
//   k6 run load-test/midad-load.js                                            -> http://127.0.0.1:8128, full run (~8 min)
//   k6 run -e BASE_URL=http://127.0.0.1:8000 -e QUICK=1 load-test/midad-load.js  -> 1-minute smoke run
// See load-test/README.md for preparing a copy of the database with the test accounts.
// Simulated visitors: 70% guests, 30% signed in (loadtest1..200@example.test, password loadtest-pass-123),
// each pausing 1-3 s between pages like a reader. Every visitor gets its own client IP (X-Forwarded-For),
// so per-visitor rate limits behave as they would with real, separate people.
import http from 'k6/http';
import { check, sleep, group } from 'k6';

const BASE = (__ENV.BASE_URL || 'http://127.0.0.1:8128').replace(/\/$/, '');
const QUICK = !!__ENV.QUICK;
const PASSWORD = __ENV.LOADTEST_PASSWORD || 'loadtest-pass-123';
// Quote ids to open: QUOTE_IDS=1,2,3 or, by default, the ones linked from the first library pages (read once in setup()).
const ENV_IDS = (__ENV.QUOTE_IDS || '').split(',').filter(Boolean);
const AUTHORS = ['محمود درويش', 'أبو العلاء المعري', 'إيليا أبو ماضي', 'المتنبي', 'جبران خليل جبران'];
const CATEGORIES = ['تأمل', 'حياة', 'حب', 'أمل'];
const SEARCHES = ['الحياة', 'الحب', 'الأمل', 'الوطن'];

export const options = {
    noCookiesReset: true, // a visitor keeps its cookies (session, sign-in) from one round to the next, like a browser
    scenarios: {
        visitors: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: QUICK
                ? [{ duration: '20s', target: 50 }, { duration: '30s', target: 50 }, { duration: '10s', target: 0 }]
                : [
                    { duration: '1m', target: 100 },   // ramp to 100 visitors
                    { duration: '2m30s', target: 100 }, // hold 100
                    { duration: '1m', target: 200 },   // ramp to 200 visitors
                    { duration: '2m30s', target: 200 }, // hold 200
                    { duration: '30s', target: 0 },
                ],
            gracefulRampDown: '20s',
        },
    },
    thresholds: Object.assign({
        http_req_duration: ['p(95)<500'],
        http_req_failed: ['rate<0.01'],
    }, Object.fromEntries(['GET /', 'GET /quotes', 'GET /quotes?page', 'GET /quotes?category', 'GET /quotes?q', 'GET /quotes/{id}',
        'GET /discover', 'GET /discover/feed', 'GET static pages', 'GET /authors/{name}', 'GET /login', 'POST /login',
        'POST /quotes/{id}/like', 'static asset'].map((name) => [`http_req_duration{name:${name}}`, ['max>=0']]))), // per-page breakdown only
    summaryTrendStats: ['avg', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

const pick = (list) => list[Math.floor(Math.random() * list.length)];
const think = () => sleep(1 + Math.random() * 2);

// Per-visitor state (each VU is one visitor with its own cookie jar).
let signedIn = false;
let assetsLoaded = false;
let csrf = '';
const member = () => __VU % 10 < 3;
const headers = (extra = {}) => Object.assign({ 'X-Forwarded-For': `10.${(__VU >> 8) & 255}.${__VU & 255}.7`, 'Accept-Encoding': 'br, gzip' }, extra);

function page(path, name) {
    const res = http.get(BASE + path, { headers: headers(), tags: { name } });
    check(res, { [`${name} 200`]: (r) => r.status === 200 });
    const token = res.body && res.body.match(/name="csrf-token" content="([^"]+)"/);
    if (token) csrf = token[1];
    if (!assetsLoaded && res.status === 200) {
        // First visit: the browser downloads the stylesheet and script once, then caches them for a year.
        const assets = [...new Set((res.body.match(/\/build\/assets\/[^"]+\.(css|js)/g) || []))];
        http.batch(assets.map((a) => ['GET', BASE + a, null, { headers: headers(), tags: { name: 'static asset' } }]));
        assetsLoaded = true;
    }
    return res;
}

function signIn() {
    const form = page('/login', 'GET /login');
    const token = form.body.match(/name="_token" value="([^"]+)"/);
    const res = http.post(BASE + '/login', { _token: token ? token[1] : '', email: `loadtest${((__VU - 1) % 200) + 1}@example.test`, password: PASSWORD },
        { headers: headers(), tags: { name: 'POST /login' }, redirects: 0 });
    signedIn = check(res, { 'POST /login redirects': (r) => r.status === 302 && !/\/login/.test(r.headers.Location || '') });
}

function discover() {
    const tab = pick(['for-you', 'for-you', 'latest', 'popular']);
    const res = page(`/discover?tab=${tab}`, 'GET /discover');
    let next = (res.body.match(/data-next="([^"]*)"/) || [])[1];
    const feed = ((res.body.match(/data-feed-url="([^"]*)"/) || [])[1] || '').replace(/&amp;/g, '&');
    // A reader scrolls through two more batches.
    for (let i = 0; i < 2 && next && feed; i++) {
        think();
        const batch = http.get(`${feed}${feed.includes('?') ? '&' : '?'}cursor=${encodeURIComponent(next.replace(/&amp;/g, '&'))}`,
            { headers: headers({ Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }), tags: { name: 'GET /discover/feed' } });
        check(batch, { 'GET /discover/feed 200': (r) => r.status === 200 });
        next = batch.status === 200 ? batch.json('next') : null;
    }
}

function like(ids) {
    const id = pick(ids);
    // Like from the quote's own page, with the token that page carries, as a reader would.
    page(`/quotes/${id}`, 'GET /quotes/{id}');
    sleep(0.5);
    const opts = { headers: headers({ Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }), tags: { name: 'POST /quotes/{id}/like' } };
    // Like, then unlike after a moment, so the data stays as it was.
    const first = http.post(`${BASE}/quotes/${id}/like`, null, opts);
    check(first, { 'POST like 200': (r) => r.status === 200 });
    sleep(0.5);
    http.post(`${BASE}/quotes/${id}/like`, null, opts);
}

export function setup() {
    if (ENV_IDS.length) return { ids: ENV_IDS };
    const ids = new Set();
    for (let n = 1; n <= 5; n++) {
        const res = http.get(`${BASE}/quotes?page=${n}`, { headers: { 'X-Forwarded-For': '10.255.255.1' } });
        (res.body || '').replace(/\/quotes\/(\d+)"/g, (_, id) => ids.add(id));
    }
    if (!ids.size) throw new Error(`No quotes found at ${BASE}/quotes - is the site running with data?`);
    return { ids: [...ids] };
}

export default function (data) {
    const QUOTE_IDS = data.ids;
    if (member() && !signedIn) {
        group('sign in', signIn);
        think();
    }

    const roll = Math.random();
    if (roll < 0.25) {
        page('/', 'GET /');
    } else if (roll < 0.45) {
        const r = Math.random();
        if (r < 0.5) page('/quotes', 'GET /quotes');
        else if (r < 0.7) page(`/quotes?page=${2 + Math.floor(Math.random() * 5)}`, 'GET /quotes?page');
        else if (r < 0.85) page(`/quotes?category=${encodeURIComponent(pick(CATEGORIES))}`, 'GET /quotes?category');
        else page(`/quotes?q=${encodeURIComponent(pick(SEARCHES))}`, 'GET /quotes?q');
    } else if (roll < 0.65) {
        page(`/quotes/${pick(QUOTE_IDS) || 2}`, 'GET /quotes/{id}');
    } else if (roll < 0.8) {
        discover();
    } else if (roll < 0.9) {
        page(pick(['/poets', '/about', '/muallaqat']), 'GET static pages');
    } else if (roll < 0.95) {
        page(`/authors/${encodeURIComponent(pick(AUTHORS))}`, 'GET /authors/{name}');
    } else if (signedIn && csrf) {
        like(QUOTE_IDS);
    } else {
        page('/', 'GET /');
    }
    think();
}

export function handleSummary(data) {
    const out = {};
    if (__ENV.SUMMARY_JSON) out[__ENV.SUMMARY_JSON] = JSON.stringify(data, null, 1);
    out.stdout = textSummary(data);
    return out;
}

function textSummary(data) {
    const m = data.metrics;
    const d = m.http_req_duration.values;
    const lines = [
        `requests: ${m.http_reqs.values.count}  (${m.http_reqs.values.rate.toFixed(1)}/s)`,
        `duration: avg ${d.avg.toFixed(0)} ms  median ${d.med.toFixed(0)}  p95 ${d['p(95)'].toFixed(0)}  p99 ${d['p(99)'].toFixed(0)}  max ${d.max.toFixed(0)}`,
        `failed:   ${(m.http_req_failed.values.rate * 100).toFixed(2)}%`,
        `checks:   ${(m.checks.values.rate * 100).toFixed(2)}% passed`,
    ];
    lines.push(`target p95 < 500 ms: ${d['p(95)'] < 500 ? 'PASS' : 'FAIL'}   target errors < 1%: ${m.http_req_failed.values.rate < 0.01 ? 'PASS' : 'FAIL'}`);
    const walk = (g) => [...(g.checks || []), ...(g.groups || []).flatMap(walk)];
    for (const c of walk(data.root_group)) if (c.fails) lines.push(`  check failed: ${c.name}  ${c.fails} of ${c.passes + c.fails}`);
    for (const [key, metric] of Object.entries(m)) {
        const tag = key.match(/^http_req_duration\{name:(.+)\}$/);
        if (tag) lines.push(`  ${tag[1].padEnd(26)} avg ${metric.values.avg.toFixed(0).padStart(5)} ms  p95 ${metric.values['p(95)'].toFixed(0).padStart(5)} ms`);
    }
    return lines.join('\n') + '\n';
}
