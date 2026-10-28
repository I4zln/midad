{{-- One batch of «اكتشف» (first one with the page, the rest from discover.feed). The same card is a full screen in «تأمّل» and a timeline card in «الخط الزمني». --}}
@foreach ($quotes as $quote)
    <x-quote-card :quote="$quote" :feed="true"
        :return-to="route('discover', array_filter(['tab' => $tab !== 'for-you' ? $tab : null, 'category' => $category ?: null, 'quote' => $quote->id]), false)"
        data-quote-id="{{ $quote->id }}" data-tone="{{ $quote->id % 5 }}" tabindex="-1" aria-setsize="-1" />
@endforeach
