@extends('layouts.app')
@section('title', 'تعديل اقتباس — مِداد')
@section('content')
<main class="inner-page quote-form-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('quotes.index') }}">الاقتباسات</a><span>←</span><a href="{{ route('quotes.show', $quote) }}">اقتباسي</a><span>←</span><span>تعديل</span></nav>
    <header class="quote-form-heading"><div class="eyebrow"><span class="eyebrow-line"></span> مساحة اقتباسك</div><h1>عدّل <em>كلماتك.</em></h1><p>يمكنك تحديث النص أو بياناته. الحد المسموح للنص من 3 إلى 155 حرفًا.</p></header>
    <form method="POST" action="{{ route('quotes.update', $quote) }}" class="account-form quote-form">
        @csrf @method('PUT')
        <x-quote-fields :quote="$quote" />
        <x-button>حفظ التعديلات <span aria-hidden="true">←</span></x-button>
    </form>
    <form method="POST" action="{{ route('quotes.destroy', $quote) }}" class="quote-delete-form" onsubmit="return confirm('هل تريد حذف هذا الاقتباس؟')">@csrf @method('DELETE')<x-button variant="outline">حذف الاقتباس</x-button></form>
</main>
@endsection
