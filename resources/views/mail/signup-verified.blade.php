@extends('mail.layout')
@section('title', 'عضو جديد في مِداد')
@section('preheader', 'اكتمل تسجيل قارئ جديد وتأكيد بريده.')
@section('eyebrow', 'تنبيه للإدارة')
@section('heading', 'وصل قارئ جديد')
@section('content')
    <p class="m-soft" style="margin:0;color:#5f5c55;font-size:16px;line-height:1.95">اكتمل تسجيل <strong class="m-ink" style="color:#24231f">{{ $displayName }}</strong> في مِداد وتأكيد بريده.</p>
@endsection
@section('footer', 'تنبيه مختصر لا يتضمن عنوان البريد أو أي بيانات خاصة بالحساب.')
