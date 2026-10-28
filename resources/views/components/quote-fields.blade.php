@props(['quote' => null, 'prefix' => ''])
@php($text = old('text', $quote?->text ?? ''))
<x-form-field for="{{ $prefix }}text" name="text" label="نص الاقتباس" help="من 3 إلى 155 حرفًا.">
    <textarea id="{{ $prefix }}text" name="text" minlength="3" maxlength="155" data-char-counter required rows="5" placeholder="اكتب الاقتباس هنا...">{{ $text }}</textarea>
    <small class="character-count" data-character-count aria-live="polite">{{ mb_strlen($text) }} / 155</small>
</x-form-field>
@if ($quote && mb_strlen($quote->text) > 155)
    <p class="form-alert legacy-length-note" role="note">هذا الاقتباس محفوظ من قبل بطول {{ mb_strlen($quote->text) }} حرفًا، وسيبقى كما هو ما لم تعدّله. لحفظ أي تعديل اختصر النص إلى 155 حرفًا أو أقل.</p>
@endif
<x-form-field for="{{ $prefix }}author" name="author" label="اسم الشاعر أو الكاتب" help="اختياري، حتى 120 حرفًا. اسم حسابك يظهر تلقائيًا بوصفك الناشر.">
    <input id="{{ $prefix }}author" name="author" maxlength="120" value="{{ old('author', $quote?->author) }}" placeholder="مثال: محمود درويش">
</x-form-field>
<x-form-field for="{{ $prefix }}source" name="source" label="المصدر" help="اختياري، حتى 160 حرفًا.">
    <input id="{{ $prefix }}source" name="source" maxlength="160" value="{{ old('source', $quote?->source) }}" placeholder="اسم الديوان أو الكتاب">
</x-form-field>
<x-form-field for="{{ $prefix }}category" name="category" label="التصنيف" help="اختر التصنيف الأقرب لمعنى الاقتباس.">
    <select id="{{ $prefix }}category" name="category" required>
        @foreach (\App\Http\Requests\QuoteRequest::CATEGORIES as $category)
            <option value="{{ $category }}" @selected(old('category', $quote?->category ?? 'تأمل') === $category)>{{ $category }}</option>
        @endforeach
    </select>
</x-form-field>
