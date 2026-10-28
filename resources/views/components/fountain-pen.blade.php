{{-- Vintage fountain pen. Nib tip sits at (8, 112) in the viewBox; the pen leans up-right like a hand writing right-to-left.
     Colours come from CSS custom properties so the same drawing works as the welcome pen and as a small button icon. --}}
<svg {{ $attributes->merge(['class' => 'fountain-pen']) }} viewBox="0 0 132 120" aria-hidden="true" focusable="false">
    <g transform="translate(8 112) rotate(-38)">
        {{-- nib --}}
        <path class="pen-nib" d="M0 0 L31 -7.5 Q36 0 31 7.5 Z" />
        <path class="pen-nib-slit" d="M3 0 H24" />
        <circle class="pen-nib-hole" cx="24.5" cy="0" r="1.7" />
        {{-- grip section --}}
        <path class="pen-body" d="M30 -7.2 H48 V7.2 H30 Q33 0 30 -7.2 Z" />
        <rect class="pen-trim" x="47" y="-8.4" width="3.2" height="16.8" rx="1" />
        {{-- barrel --}}
        <rect class="pen-body" x="50" y="-9.4" width="70" height="18.8" rx="4" />
        <path class="pen-shine" d="M56 -5.6 H112" />
        {{-- cap band and tail --}}
        <rect class="pen-trim" x="119" y="-10" width="4" height="20" rx="1.2" />
        <path class="pen-body" d="M123 -9.4 H143 Q152 -9.4 152 0 Q152 9.4 143 9.4 H123 Z" />
        {{-- clip --}}
        <path class="pen-trim" d="M100 -11.6 H141 Q144 -11.6 144 -10.2 V-9.4 H100 Z" />
        <circle class="pen-trim" cx="101.5" cy="-11.6" r="2.4" />
    </g>
</svg>
