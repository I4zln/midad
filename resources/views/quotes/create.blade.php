@extends('layouts.app')
@section('title', 'إضافة اقتباس — مِداد')
@section('content')
<main class="inner-page quote-form-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('quotes.index') }}">الاقتباسات</a><span>←</span><span>إضافة اقتباس</span></nav>
    <header class="quote-form-heading"><div class="eyebrow"><span class="eyebrow-line"></span> صوتك جزء من المكتبة</div><h1>أضف <em>اقتباسًا.</em></h1><p>اكتب الكلمات كما تريد أن تبقى، وسيظهر اسمك وتاريخ النشر معها.</p></header>
    <form method="POST" action="{{ route('quotes.store') }}" class="account-form quote-form" data-validate>
        @csrf
        <x-quote-fields />
        <x-button>نشر الاقتباس <span aria-hidden="true">←</span></x-button>
    </form>
</main>
@endsection
