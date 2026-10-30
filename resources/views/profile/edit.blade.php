@extends('layouts.app')
@section('title', 'تعديل الملف الشخصي — مِداد')
@section('content')
<main class="inner-page profile-edit-page">
    <nav class="breadcrumbs" aria-label="مسار التنقل"><a href="{{ route('home') }}">الرئيسية</a><span>←</span><a href="{{ route('profile.show', $profile) }}">ملفي الشخصي</a><span>←</span><span>تعديل</span></nav>
    <div class="page-intro compact-intro"><div class="eyebrow"><span class="eyebrow-line"></span> مساحتك في مِداد</div><h1>ملفك <em>الشخصي.</em></h1><p>أضف نبذة وموقعًا ومهاراتك، لتظهر للقراء حين يزورون ملفك.</p></div>
    <form class="profile-edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-validate>
        @csrf @method('PATCH')
        @if ($errors->any())<div class="form-alert" role="alert">راجع الحقول المعلَّمة أدناه ثم احفظ مرة أخرى.</div>@endif
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> الصورة والغلاف</span><p>JPG أو PNG أو WebP. الصورة حتى 2MB والغلاف حتى 4MB.</p></div>
            <div class="profile-image-fields">
                <div class="profile-upload-field"><span class="upload-field-title">الصورة الشخصية</span><div class="profile-photo-preview" id="avatar-preview" data-image-preview>@if ($profile->avatar_path)<img src="{{ asset('storage/'.$profile->avatar_path) }}" alt="الصورة الحالية">@else<span>{{ mb_substr($profile->name, 0, 1) }}</span>@endif</div><input class="profile-file-input" id="avatar-upload" data-preview="avatar-preview" type="file" name="avatar" accept="image/png,image/jpeg,image/webp"><label class="profile-file-button" for="avatar-upload">اختر صورة <span aria-hidden="true">↑</span></label><small>اختر صورة جديدة عند الحاجة</small>@if ($profile->avatar_path)<label class="remove-upload"><input type="checkbox" name="remove_avatar" value="1"> حذف الصورة الحالية</label>@endif</div>
                <div class="profile-upload-field"><span class="upload-field-title">غلاف الملف الشخصي</span><div class="profile-photo-preview profile-cover-preview" id="cover-preview" data-image-preview>@if ($profile->cover_path)<img src="{{ asset('storage/'.$profile->cover_path) }}" alt="الغلاف الحالي">@else<span>مساحة هادئة لغلافك</span>@endif</div><input class="profile-file-input" id="cover-upload" data-preview="cover-preview" type="file" name="cover" accept="image/png,image/jpeg,image/webp,image/gif"><label class="profile-file-button" for="cover-upload">اختر غلافًا <span aria-hidden="true">↑</span></label><small>صورة عريضة اختيارية، ويمكن أن تكون GIF متحركة</small>@if ($profile->cover_path)<label class="remove-upload"><input type="checkbox" name="remove_cover" value="1"> حذف الغلاف الحالي</label>@endif</div>
            </div>
        </section>
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> معلوماتك</span></div>
            <x-form-field for="profile-name" name="name" label="الاسم الظاهر" help="من 2 إلى 50 حرفًا، ويظهر مع اقتباساتك.">
                <input id="profile-name" name="name" minlength="2" maxlength="50" required value="{{ old('name', $profile->name) }}" autocomplete="name">
            </x-form-field>
            <x-form-field for="profile-bio" name="bio" label="نبذة قصيرة" help="اختيارية، حتى 600 حرف.">
                <textarea id="profile-bio" name="bio" maxlength="600" rows="4" placeholder="ما الكلمات التي تحب أن يعرفها الآخرون عنك؟">{{ old('bio', $profile->bio) }}</textarea>
            </x-form-field>
            <div class="field-row">
                <x-form-field for="profile-location" name="location" label="المدينة أو الموقع" help="اختياري.">
                    <input id="profile-location" name="location" maxlength="120" value="{{ old('location', $profile->location) }}" placeholder="مكة المكرمة">
                </x-form-field>
                <x-form-field for="profile-education" name="education" label="الدراسة أو التخصص" help="اختياري.">
                    <input id="profile-education" name="education" maxlength="180" value="{{ old('education', $profile->education) }}" placeholder="هندسة البرمجيات">
                </x-form-field>
            </div>
            <x-form-field for="profile-skills" name="skills" label="المهارات" help="اختيارية، افصل بينها بفاصلة.">
                <input id="profile-skills" name="skills" maxlength="500" value="{{ old('skills', implode(', ', $profile->skills ?? [])) }}" placeholder="الكتابة، القراءة، الترجمة">
            </x-form-field>
        </section>
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> روابطك</span><p>أضف الروابط التي تريد مشاركتها مع زوار ملفك.</p></div>
            <div class="profile-link-grid">
                @foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'instagram' => 'Instagram', 'x' => 'X'] as $network => $label)
                    <x-form-field for="social-{{ $network }}" name="social_links.{{ $network }}" :label="$label">
                        <input id="social-{{ $network }}" type="url" name="social_links[{{ $network }}]" maxlength="255" dir="ltr" value="{{ old('social_links.'.$network, $profile->social_links[$network] ?? '') }}" placeholder="https://">
                    </x-form-field>
                @endforeach
            </div>
        </section>
        <section class="profile-edit-section">
            <div class="profile-edit-heading"><span class="eyebrow"><span class="eyebrow-line"></span> استعادة الحساب</span><p>إن نسيت كلمة المرور وراسلت الإدارة على واتساب، نرسل لك رابط الاستعادة فقط حين تأتي الرسالة من هذا الرقم.</p></div>
            <x-form-field for="profile-recovery-phone" name="recovery_phone" label="رقم واتساب للاستعادة" help="اختياري. لا يظهر لأي أحد، ولا تراه إلا إدارة مِداد عند استعادة حسابك.">
                <input id="profile-recovery-phone" name="recovery_phone" type="tel" inputmode="tel" maxlength="25" dir="ltr" autocomplete="tel" value="{{ old('recovery_phone', $profile->recovery_phone) }}" placeholder="05xxxxxxxx">
            </x-form-field>
        </section>
        <div class="profile-edit-actions"><a class="text-button" href="{{ route('profile.show', $profile) }}">إلغاء</a><button class="button button-dark" type="submit">حفظ الملف الشخصي <span aria-hidden="true">←</span></button></div>
    </form>

    {{-- Deleting the account: separate form, current password required, confirmed in the shared dialog. --}}
    <section class="profile-edit-section account-delete" aria-labelledby="account-delete-title">
        <h2 id="account-delete-title">حذف الحساب</h2>
        <p>يحذف حسابك نهائيًا مع اقتباساتك وإعجاباتك ومحفوظاتك وتعليقاتك ومتابعاتك وصورك. لا يمكن التراجع عن ذلك.</p>
        <form method="POST" action="{{ route('account.destroy') }}" class="account-form" data-confirm="حذف حسابك وكل بياناتك نهائيًا؟ لا يمكن التراجع.">
            @csrf
            @method('DELETE')
            <x-form-field for="delete-password" name="password" label="كلمة المرور الحالية" help="للتأكد أنك صاحب الحساب.">
                <x-password-input id="delete-password" name="password" autocomplete="current-password" />
            </x-form-field>
            <button class="button button-danger" type="submit">حذف حسابي نهائيًا</button>
        </form>
    </section>
</main>
@endsection
