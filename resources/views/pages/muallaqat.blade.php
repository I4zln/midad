@extends('layouts.app')
@section('title', 'المعلّقات السبع — مِداد')
@section('content')
<main class="inner-page muallaqat-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">مِداد</a><span>←</span><span>المعلّقات السبع</span></nav>
    <header class="muallaqat-hero">
        <span class="muallaqat-ornament" aria-hidden="true">م</span>
        <div class="eyebrow"><span class="eyebrow-line"></span> من ذاكرة القصيدة العربية</div>
        <h1>المعلّقات <em>السبع</em></h1>
        <p>سبع قصائد من أشهر ما وصلنا من الشعر الجاهلي؛ لكل واحدة صوتها وصورها وحكايتها. ابحث باسم الشاعر أو عنوان المعلّقة أو وصفها.</p>
        <label class="muallaqat-search"><span aria-hidden="true">⌕</span><input id="muallaqat-search" type="search" placeholder="ابحث عن شاعر أو معلقة…" autocomplete="off" aria-label="ابحث في المعلقات"></label>
        <p class="muallaqat-search-empty" id="muallaqat-empty" hidden>ما وجدنا نتيجة. جرّب كلمة أخرى أو امسح البحث.</p>
    </header>
    <section class="muallaqat-list" aria-label="قائمة المعلقات">
        @foreach ($muallaqat as $index => $poem)
        <article class="muallaqa-card" data-search="{{ $poem['poet'] }} {{ $poem['title'] }} {{ $poem['tagline'] }} {{ $poem['bio'] }}">
            <a class="muallaqa-image" href="{{ route('muallaqat.show', $poem['slug']) }}" aria-label="افتح معلقة {{ $poem['poet'] }}">
                <img src="{{ str_starts_with($poem['image'], 'http') ? $poem['image'] : asset($poem['image']) }}" alt="{{ $poem['image_note'] }}" loading="lazy" data-fallback-src="{{ asset('images/poets/literary-scribe.jpg') }}">
                <span class="muallaqa-image-shade"></span><span class="muallaqa-number">{{ sprintf('%02d', $index + 1) }}</span><span class="muallaqa-image-name">{{ $poem['poet'] }}</span>
            </a>
            <div class="muallaqa-card-copy"><div class="eyebrow"><span class="eyebrow-line"></span> المعلّقة {{ sprintf('%02d', $index + 1) }}</div><h2><a href="{{ route('muallaqat.show', $poem['slug']) }}">{{ $poem['title'] }}</a></h2><p>{{ $poem['tagline'] }}</p><a class="muallaqa-read-link" href="{{ route('muallaqat.show', $poem['slug']) }}">اقرأ عن المعلّقة <span aria-hidden="true">←</span></a>
            <small class="muallaqa-source-note">مرجع الصورة: <a href="{{ str_starts_with($poem['image_source'], 'http') ? $poem['image_source'] : asset($poem['image_source']) }}" target="_blank" rel="noopener noreferrer">{{ $poem['image_note'] }}</a></small></div>
        </article>
        @endforeach
    </section>
    <p class="muallaqat-caveat">اعتمدنا التصنيف الشائع للمعلّقات السبع، مع الإشارة إلى أن عدد القصائد ورواياتها اختلفت في كتب التراث.</p>
</main>
@endsection
@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
const searchInput = document.querySelector('#muallaqat-search');
if (searchInput) {
    const cards = [...document.querySelectorAll('.muallaqa-card')];
    const empty = document.querySelector('#muallaqat-empty');
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLocaleLowerCase('ar');
        let visible = 0;
        cards.forEach(card => {
            const matches = card.dataset.search.toLocaleLowerCase('ar').includes(query);
            card.hidden = !matches;
            if (matches) visible++;
        });
        empty.hidden = visible > 0;
    });
}
</script>
@endpush