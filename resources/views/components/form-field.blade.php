@props(['for', 'label', 'help' => null, 'name' => null])
<label class="field-label" for="{{ $for }}">{{ $label }} {{ $slot }}
    @if ($help)<small class="composer-field-hint">{{ $help }}</small>@endif
    @error($name ?? $for)<small class="field-error">{{ $message }}</small>@enderror
</label>
