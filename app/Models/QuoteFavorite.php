<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteFavorite extends Model
{
    protected $fillable = ['quote_id', 'user_id'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
