@extends('layouts.app')
@section('title', 'تعديل اقتباس — مِداد')
@section('content')
<main class="inner-page quote-form-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('quotes.index') }}">الاقتباسات</a><span>←</span><a href="{{ route('quotes.show', $quote) }}">اقتباسي</a><span>←</span><span>تعديل</span></nav>
    <header class="quote-form-heading"><div class="eyebrow"><span class="eyebrow-line"></span> مساحة اقتباسك</div><h1>عدّل <em>كلماتك.</em></h1><p>يمكنك تحديث النص أو بياناته. الحد المسموح للنص من 3 إلى 155 حرفًا.</p></header>
    <form method="POST" action="{{ route('quotes.update', $quote) }}" class="account-form quote-form">
        @csrf @method('PUT')
        <x-form-field for="text" label="نص الاقتباس" help="من 3 إلى 155 حرفًا."><textarea id="text" name="text" minlength="3" maxlength="155" data-char-counter required rows="5">{{ old('text', $quote->text) }}</textarea><small class="character-count" data-character-count aria-live="polite">{{ mb_strlen(old('text', $quote->text)) }} / 155</small></x-form-field>
        <x-form-field for="author" label="اسم الشاعر أو الكاتب" help="اختياري، حتى 120 حرفًا."><input id="author" name="author" maxlength="120" value="{{ old('author', $quote->author) }}"></x-form-field>
        <x-form-field for="source" label="المصدر" help="اختياري، حتى 160 حرفًا."><input id="source" name="source" maxlength="160" value="{{ old('source', $quote->source) }}"></x-form-field>
        <x-form-field for="category" label="التصنيف" help="اختر التصنيف الأقرب لمعنى الاقتباس."><select id="category" name="category" required>@foreach (['تأمل', 'حياة', 'حب', 'أمل'] as $category)<option value="{{ $category }}" @selected(old('category', $quote->category) === $category)>{{ $category }}</option>@endforeach</select></x-form-field>
        <x-button>حفظ التعديلات <span aria-hidden="true">←</span></x-button>
    </form>
    <form method="POST" action="{{ route('quotes.destroy', $quote) }}" class="quote-delete-form" onsubmit="return confirm('هل تريد حذف هذا الاقتباس؟')">@csrf @method('DELETE')<x-button variant="outline">حذف الاقتباس</x-button></form>
</main>
@endsection
