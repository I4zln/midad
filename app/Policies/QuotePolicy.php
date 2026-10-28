<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    public function update(User $user, Quote $quote): bool
    {
        return $quote->user_id !== null && $quote->user_id === $user->id;
    }

    public function delete(User $user, Quote $quote): bool
    {
        return $quote->user_id !== null && $quote->user_id === $user->id;
    }
}
