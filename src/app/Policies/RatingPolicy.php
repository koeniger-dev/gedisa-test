<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rating;
use App\Models\User;

final class RatingPolicy
{
    /**
     * Only the rating's owner may change it.
     */
    public function update(User $user, Rating $rating): bool
    {
        return $user->id === $rating->user_id;
    }
}
