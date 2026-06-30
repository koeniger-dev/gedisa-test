<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the database-level invariants for ratings — the last line of defence
 * regardless of the application code that writes them.
 */
final class RatingConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_rate_the_same_movie_twice(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Rating::factory()->for($user)->for($movie)->create(['rating' => 3]);

        // unique(user_id, movie_id) must reject a second row for the same pair.
        $this->expectException(QueryException::class);

        Rating::factory()->for($user)->for($movie)->create(['rating' => 4]);
    }

    public function test_rating_below_one_is_rejected_by_check_constraint(): void
    {
        $this->expectException(QueryException::class);

        Rating::factory()->create(['rating' => 0]);
    }

    public function test_rating_above_five_is_rejected_by_check_constraint(): void
    {
        $this->expectException(QueryException::class);

        Rating::factory()->create(['rating' => 6]);
    }
}
