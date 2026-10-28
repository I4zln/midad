@props(['href' => null, 'variant' => 'dark', 'type' => 'submit'])
@if ($href)
    <a {{ $attributes->class(['button', 'button-dark' => $variant === 'dark', 'button-outline' => $variant === 'outline', 'button-light' => $variant === 'light']) }} href="{{ $href }}">{{ $slot }}</a>
@else
    <button {{ $attributes->class(['button', 'button-dark' => $variant === 'dark', 'button-outline' => $variant === 'outline', 'button-light' => $variant === 'light']) }} type="{{ $type }}">{{ $slot }}</button>
@endif
