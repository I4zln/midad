@props(['letter' => 'ع'])
{{-- Copper ink stamp: rough-edged double ring around one letter. The roughness filter makes it read as pressed ink, not a flat circle. --}}
<svg {{ $attributes->merge(['class' => 'ink-seal']) }} viewBox="0 0 120 120" aria-hidden="true" focusable="false">
    <defs>
        <filter id="ink-seal-rough" x="-10%" y="-10%" width="120%" height="120%">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="7" result="noise" />
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="2.6" />
        </filter>
    </defs>
    <g filter="url(#ink-seal-rough)">
        <circle class="ink-seal-fill" cx="60" cy="60" r="53" />
        <circle class="ink-seal-ring" cx="60" cy="60" r="53" stroke-width="3" />
        <circle class="ink-seal-ring" cx="60" cy="60" r="45" stroke-width="1.2" />
        <text class="ink-seal-letter" x="60" y="62" text-anchor="middle" dominant-baseline="middle">{{ $letter }}</text>
    </g>
</svg>
