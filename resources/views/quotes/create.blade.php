@extends('layouts.app')
@section('title', 'إضافة اقتباس — مِداد')
@section('content')
<main class="inner-page quote-form-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('quotes.index') }}">الاقتباسات</a><span>←</span><span>إضافة اقتباس</span></nav>
    <header class="quote-form-heading"><div class="eyebrow"><span class="eyebrow-line"></span> صوتك جزء من المكتبة</div><h1>أضف <em>اقتباسًا.</em></h1><p>اكتب الكلمات كما تريد أن تبقى، وسيظهر اسمك وتاريخ النشر معها.</p></header>
    <form method="POST" action="{{ route('quotes.store') }}" class="account-form quote-form">
        @csrf
        <x-form-field for="text" label="نص الاقتباس" help="من 3 إلى 155 حرفًا."><textarea id="text" name="text" minlength="3" maxlength="155" data-char-counter required rows="5">{{ old('text') }}</textarea><small class="character-count" data-character-count aria-live="polite">0 / 155</small></x-form-field>
        <x-form-field for="author" label="اسم الشاعر أو الكاتب" help="اختياري، حتى 120 حرفًا."><input id="author" name="author" maxlength="120" value="{{ old('author') }}"></x-form-field>
        <x-form-field for="source" label="المصدر" help="اختياري، حتى 160 حرفًا."><input id="source" name="source" maxlength="160" value="{{ old('source') }}"></x-form-field>
        <x-form-field for="category" label="التصنيف" help="اختر التصنيف الأقرب لمعنى الاقتباس."><select id="category" name="category" required>@foreach (['تأمل', 'حياة', 'حب', 'أمل'] as $category)<option value="{{ $category }}" @selected(old('category', 'تأمل') === $category)>{{ $category }}</option>@endforeach</select></x-form-field>
        <x-button>نشر الاقتباس <span aria-hidden="true">←</span></x-button>
    </form>
</main>
@endsection
