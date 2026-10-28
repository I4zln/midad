<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteLike extends Model
{
    protected $fillable = ['quote_id', 'user_id'];
}
