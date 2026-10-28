# How «اكتشف» orders quotes

«اكتشف» (`/discover`) has three tabs. Two of them are plain orders. One is a ranked feed built from a few simple rules. There is no machine learning, no tracking outside the site, and no data beyond what MIDAD already stores: quotes, likes, saves (المحفوظات) and categories.

| Tab | Order |
|---|---|
| **مختارة لك** (guests: **مختارات**) | Ranked by the rules below |
| **الأحدث** | Newest first |
| **الأشهر** | Most likes and saves first, newer first on a tie |

The category chips work on every tab and show only quotes in that category. «الكل» removes the filter.

Code: [`app/Services/FeedRanker.php`](../app/Services/FeedRanker.php) holds the rules and [`app/Services/DiscoverFeed.php`](../app/Services/DiscoverFeed.php) handles loading. Every number below is a setting in [`config/feed.php`](../config/feed.php).

---

## 1. Which quotes are considered

The candidates are the **400 newest quotes** plus the **150 most liked and saved**, with duplicates removed. That list and its like and save counts are cached for **2 minutes**. The cache is also cleared whenever a quote is added, edited or deleted, so a new quote appears in the next feed straight away.

## 2. A score for each quote

Every signal is a number between 0 and 1, multiplied by its weight. The weights are added together.

| Signal | What it measures | Weight (members) | Weight (guests) |
|---|---|---|---|
| Freshness | 1 for a quote posted now, halving every **72 hours** (0.5 after 3 days, 0.25 after 6) | **1.0** | **1.0** |
| Appreciation | `log(1 + likes + 1.5 × saves) / log(51)`, capped at 1 | **0.9** | **1.0** |
| Category affinity | The share of your own likes and saves that fall in this quote's category | **0.7** | — |
| Author affinity | How many quotes by this author you liked or saved; counts in full from **3** | **0.5** | — |
| Discovery | A random number fixed for this feed, so the order holds while you scroll and changes on your next visit | **0.35** | **0.35** |
| Your own quote | 1 if you posted it | **−0.6** | — |

A quote with no author counts under the member who published it.

**Why these weights**

- **Freshness and appreciation are about equal.** A quote posted today with no likes yet should compete with a week-old quote that readers liked. If either one dominated, the feed would be only new quotes or only old favourites.
- **Appreciation uses a log curve with a cap,** so one viral quote cannot take over. The first like adds about 0.18, ten likes add about 0.61, and 50 or more add the full 1.0. A thousand likes rank no higher than fifty.
- **Personal signals are smaller than the two main ones combined.** Your taste nudges the feed instead of filtering it, so you still see other categories.
- **Discovery is small (0.35),** enough to reshuffle quotes with similar scores but not to put a weak quote above a strong one.
- **Own quotes are pushed back (−0.6).** You do not need to discover what you wrote. They still appear, only later.
- **Saves count 1.5 times a like.** Saving a quote is a stronger signal of value than a quick like.

## 3. Rules applied on top of the score

1. **What you already liked or saved comes last.** Those quotes go after everything new to you, whatever their score.
2. **What you were already shown this session comes after unseen quotes.** The site remembers up to 400 quote numbers in your session, on the server. This is why a new visit starts with quotes you have not seen yet. The list ends with the session.
3. **Variety.** The same author, or the same category, never appears more than **twice in a row** while any other quote is left to put between them. With a category chip on, only the author rule applies.
4. **Room for newcomers.** Every **4th** place goes to the best-scoring quote that is newer than **7 days** and has at most **2** likes and saves, if one is left. This mixes newer quotes in with proven ones.

These rules only reorder quotes within the same group. Rule 3 or 4 never lifts a liked or already-seen quote above an unseen one.

## 4. Guests

Guests have no likes to learn from, so they get the even mix in the guests column: freshness, appreciation, and the small random element. In practice that means a blend of what is new and what readers are liking. Rules 3 and 4 still apply.

## 5. Loading: batches, cursors, no repeats

- The page arrives with the first **12** quotes already in it, so it paints at once. The next 12 are requested about three screens before you reach the end.
- **الأحدث** uses a keyset cursor on `(created_at, id)`, following the existing `quotes_latest_index`. There is no offset paging.
- **مختارة لك** and **الأشهر** are ranked once, when the feed starts. That order is then **frozen for 30 minutes** in the cache for that reader. The cursor is an unguessable token plus a position in the frozen list, so likes or new quotes arriving while you scroll can never repeat or skip a quote. A member's frozen list can only be continued by that member.
- The browser also never inserts a quote that is already on screen, as a second safeguard.
- Each batch costs two data queries, whatever its size: the quotes (with like counts and your like and save state) and their publishers. Everything else on the request is session, sign-in, rate-limit and cache lookups. There is no N+1: a test checks the count stays the same for 3 or 40 quotes.

## 6. Tuning

Change the numbers in `config/feed.php`. On the production copy, refresh the cached config afterwards (`php artisan optimize` with the production environment, as in `DEPLOY.md`). The tests in `tests/Feature/DiscoverFeedTest.php` check the rules, not the exact weights, so they keep passing after reasonable changes.
