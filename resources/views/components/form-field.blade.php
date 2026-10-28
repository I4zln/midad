@props(['for', 'label', 'help' => null, 'name' => null])
<label class="field-label" for="{{ $for }}">{{ $label }} {{ $slot }}
    @if ($help)<small class="composer-field-hint" id="{{ $for }}-hint">{{ $help }}</small>@endif
    @error($name ?? $for)<small class="field-error" id="{{ $for }}-error" role="alert">{{ $message }}</small>@enderror
</label>
