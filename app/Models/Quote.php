<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Quote extends Model
{
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
