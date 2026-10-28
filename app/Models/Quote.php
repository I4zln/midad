<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
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
