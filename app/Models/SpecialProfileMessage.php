<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialProfileMessage extends Model
{
    protected $fillable = ['user_id', 'message_key', 'title', 'body', 'sender_name', 'sender_role', 'shown_at'];

    protected function casts(): array
    {
        return ['shown_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
