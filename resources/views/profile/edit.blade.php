@extends('layouts.app')
@section('title', 'تعديل الملف الشخصي — مِداد')
@section('content')
<main class="inner-page profile-edit-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><a href="{{ route('profile.show', $profile) }}">ملفي الشخصي</a><span>←</span><span>تعديل</span></nav>
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> مساحتك في مِداد</div><h1>ملفك <em>الشخصي.</em></h1><p>أضف نبذة وموقعًا ومهاراتك، لتظهر للقراء حين يزورون ملفك.</p></div>
    <form class="profile-edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        @if ($errors->any())<div class="form-alert" role="alert">{{ $errors->first() }}</div>@endif
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> الصورة والغلاف</span><p>JPG أو PNG أو WebP. الصورة حتى 2MB والغلاف حتى 4MB.</p></div>
            <div class="profile-image-fields">
                <div class="profile-upload-field"><span class="upload-field-title">الصورة الشخصية</span><div class="profile-photo-preview" id="avatar-preview" data-image-preview>@if ($profile->avatar_path)<img src="{{ asset('storage/'.$profile->avatar_path) }}" alt="الصورة الحالية">@else<span>{{ mb_substr($profile->name, 0, 1) }}</span>@endif</div><input class="profile-file-input" id="avatar-upload" data-preview="avatar-preview" type="file" name="avatar" accept="image/png,image/jpeg,image/webp"><label class="profile-file-button" for="avatar-upload">اختر صورة <span aria-hidden="true">↑</span></label><small>اختر صورة جديدة عند الحاجة</small>@if ($profile->avatar_path)<label class="remove-upload"><input type="checkbox" name="remove_avatar" value="1"> حذف الصورة الحالية</label>@endif</div>
                <div class="profile-upload-field"><span class="upload-field-title">غلاف الملف الشخصي</span><div class="profile-photo-preview profile-cover-preview" id="cover-preview" data-image-preview>@if ($profile->cover_path)<img src="{{ asset('storage/'.$profile->cover_path) }}" alt="الغلاف الحالي">@else<span>مساحة هادئة لغلافك</span>@endif</div><input class="profile-file-input" id="cover-upload" data-preview="cover-preview" type="file" name="cover" accept="image/png,image/jpeg,image/webp"><label class="profile-file-button" for="cover-upload">اختر غلافًا <span aria-hidden="true">↑</span></label><small>صورة عريضة اختيارية</small>@if ($profile->cover_path)<label class="remove-upload"><input type="checkbox" name="remove_cover" value="1"> حذف الغلاف الحالي</label>@endif</div>
            </div>
        </section>
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> معلوماتك</span></div>
            <label class="field-label" for="profile-name">الاسم الظاهر<input id="profile-name" name="name" maxlength="120" required value="{{ old('name', $profile->name) }}"></label>
            <label class="field-label" for="profile-bio">نبذة قصيرة<textarea id="profile-bio" name="bio" maxlength="600" rows="4" placeholder="ما الكلمات التي تحب أن يعرفها الآخرون عنك؟">{{ old('bio', $profile->bio) }}</textarea><small>حتى 600 حرف</small></label>
            <div class="field-row">
                <label class="field-label" for="profile-location">المدينة أو الموقع<input id="profile-location" name="location" maxlength="120" value="{{ old('location', $profile->location) }}" placeholder="مكة المكرمة"></label>
                <label class="field-label" for="profile-education">الدراسة أو التخصص<input id="profile-education" name="education" maxlength="180" value="{{ old('education', $profile->education) }}" placeholder="هندسة البرمجيات"></label>
            </div>
            <label class="field-label" for="profile-skills">المهارات <small>افصل بينها بفاصلة</small><input id="profile-skills" name="skills" maxlength="500" value="{{ old('skills', implode(', ', $profile->skills ?? [])) }}" placeholder="الكتابة، القراءة، الترجمة"></label>
        </section>
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> روابطك</span><p>أضف الروابط التي تريد مشاركتها مع زوار ملفك.</p></div>
            <div class="profile-link-grid">
                @foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'x' => 'X'] as $network => $label)
                    <label class="field-label" for="social-{{ $network }}">{{ $label }}<input id="social-{{ $network }}" type="url" name="social_links[{{ $network }}]" maxlength="255" value="{{ old('social_links.'.$network, $profile->social_links[$network] ?? '') }}" placeholder="https://"></label>
                @endforeach
            </div>
        </section>
        <div class="profile-edit-actions"><a class="text-button" href="{{ route('profile.show', $profile) }}">إلغاء</a><button class="button button-dark" type="submit">حفظ الملف الشخصي <span aria-hidden="true">←</span></button></div>
    </form>
</main>
@endsection
