@props(['for', 'label', 'help' => null])
<label class="field-label" for="{{ $for }}">{{ $label }} {{ $slot }}
    @if ($help)<small class="composer-field-hint">{{ $help }}</small>@endif
    @error($for)<small class="field-error">{{ $message }}</small>@enderror
</label>
