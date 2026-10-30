<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteLike extends Model
{
    protected static function booted(): void
    {
        // Likes and comments rank «الأكثر تفاعلًا» on the home page.
        $forget = fn () => \Illuminate\Support\Facades\Cache::forget(Quote::LEADERBOARD_CACHE);
        static::created($forget);
        static::deleted($forget);
    }

    protected $fillable = ['quote_id', 'user_id'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
