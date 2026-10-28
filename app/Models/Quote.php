<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Quote extends Model
{
    private const AUTHOR_COUNTS_CACHE = 'midad.author-counts';

    protected static function booted(): void
    {
        // Any added, edited or deleted quote can change the per-author counts.
        $forget = fn () => Cache::forget(self::AUTHOR_COUNTS_CACHE);
        static::saved($forget);
        static::deleted($forget);
    }

    /** Authors with how many quotes each (home "أصوات كتبها القرّاء" and the poets page), cached until a quote changes. */
    public static function authorCounts(): Collection
    {
        return Cache::rememberForever(self::AUTHOR_COUNTS_CACHE, fn () => static::query()
            ->selectRaw('author, COUNT(*) as quotes_count')
            ->whereNotNull('author')->where('author', '!=', '')
            ->groupBy('author')->orderByDesc('quotes_count')->orderBy('author')
            ->toBase()->get()
            ->map(fn ($row) => (object) ['author' => $row->author, 'quotes_count' => (int) $row->quotes_count]));
    }

    // Everything <x-quote-card> renders, loaded up front so card lists never query inside the loop.
    public function scopeWithCardData(Builder $query): Builder
    {
        return $query->with('user')->withCount(['likes', 'comments'])
            ->when(Auth::check(), fn (Builder $query) => $query
                ->withExists(['likes as is_liked' => fn ($likes) => $likes->where('user_id', Auth::id())])
                ->withExists(['favorites as is_favorited' => fn ($favorites) => $favorites->where('user_id', Auth::id())]));
    }

    protected $fillable = [
        'user_id',
        'text',
        'author',
        'source',
        'category',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(QuoteComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(QuoteLike::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(QuoteFavorite::class);
    }
}
