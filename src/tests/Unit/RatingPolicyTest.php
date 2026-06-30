<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Rating;
use App\Models\User;
use App\Policies\RatingPolicy;
use Tests\TestCase;

final class RatingPolicyTest extends TestCase
{
    private function userWithId(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    private function ratingOwnedBy(int $userId): Rating
    {
        $rating = new Rating;
        $rating->user_id = $userId;

        return $rating;
    }

    public function test_owner_may_update_their_rating(): void
    {
        $this->assertTrue(
            (new RatingPolicy)->update($this->userWithId(1), $this->ratingOwnedBy(1)),
        );
    }

    public function test_other_user_may_not_update_someone_elses_rating(): void
    {
        $this->assertFalse(
            (new RatingPolicy)->update($this->userWithId(1), $this->ratingOwnedBy(2)),
        );
    }
}
