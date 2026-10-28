{{-- Bulletproof email button. $variant: 'primary' (ink) or 'secondary' (copper outline). --}}
@php($primary = ($variant ?? 'primary') === 'primary')
<td class="m-button {{ $primary ? 'm-primary' : 'm-secondary' }} {{ $stack ?? false ? 'm-stack' : '' }}" style="{{ $primary ? 'background:#24231f;' : 'border:1px solid #8f5c39;' }}border-radius:10px;padding:0">
    <a href="{{ $url }}" style="display:inline-block;padding:{{ $primary ? '14px 28px' : '13px 26px' }};color:{{ $primary ? '#fbf9f4' : '#8f5c39' }};font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:15px;font-weight:bold;line-height:1.4;text-decoration:none;border-radius:10px">{{ $label }}</a>
</td>
