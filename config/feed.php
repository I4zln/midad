<?php

// Weights for the «اكتشف» feed (App\Services\FeedRanker). docs/FEED_ALGORITHM.md explains each one in plain language.
// Every signal is scaled to 0–1 before its weight is applied, so the weights compare directly.
return [
    // Quotes per request. The first batch is rendered with the page; the rest load as the reader scrolls.
    'batch_size' => 12,

    // Candidate pool: the newest quotes plus the most appreciated ones, cached briefly.
    'candidates' => [
        'latest' => 400,
        'popular' => 150,
        'cache_seconds' => 120,
    ],

    // A ranked feed is frozen when it starts, so scrolling never repeats or skips a quote while likes change.
    'snapshot_minutes' => 30,

    'weights' => [
        // Recency: 1 for a quote posted now, halving every `freshness_half_life_hours`.
        'freshness' => 1.0,
        // Likes and saves on a log curve (a save counts as `save_weight` likes), capped at 1 when they reach `appreciation_cap`.
        'appreciation' => 0.9,
        // Signed-in readers: share of their own likes and saves in the quote's category.
        'category_affinity' => 0.7,
        // Signed-in readers: quotes by an author (or publisher) they liked or saved before, full at `author_affinity_full` interactions.
        'author_affinity' => 0.5,
        // A seeded random 0–1 per feed, so every visit feels a little different and new quotes get seen.
        'discovery' => 0.35,
        // Readers do not need to discover their own quotes.
        'own_quote' => -0.6,
    ],

    // Guests have no likes to learn from: an even mix of what is new and what readers appreciate.
    'guest_weights' => [
        'freshness' => 1.0,
        'appreciation' => 1.0,
        'discovery' => 0.35,
    ],

    'freshness_half_life_hours' => 72,
    'save_weight' => 1.5,
    'appreciation_cap' => 50,
    'author_affinity_full' => 3,

    // Diversity: at most this many quotes in a row from the same author, or from the same category,
    // whenever any other quote is left to put in between.
    'max_in_a_row' => 2,

    // Every Nth place goes to a newcomer (newer than `newcomer_days`, at most `newcomer_max_reactions` likes and saves) when one is left.
    'newcomer_every' => 4,
    'newcomer_days' => 7,
    'newcomer_max_reactions' => 2,

    // Quotes already shown in this browser session move behind unseen ones; this many are remembered.
    'seen_memory' => 400,
];
