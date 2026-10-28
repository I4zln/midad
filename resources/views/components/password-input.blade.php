@props(['id', 'name' => null, 'autocomplete' => 'current-password', 'minlength' => null])
<span class="password-field">
    <input id="{{ $id }}" name="{{ $name ?? $id }}" type="password" autocomplete="{{ $autocomplete }}" @if ($minlength) minlength="{{ $minlength }}" @endif dir="ltr" required {{ $attributes }}>
    <button class="password-visibility" type="button" data-toggle-password="{{ $id }}" aria-pressed="false" aria-label="إظهار كلمة المرور">إظهار</button>
</span>
