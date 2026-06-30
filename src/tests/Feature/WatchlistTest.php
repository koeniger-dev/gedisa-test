<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\MovieDetail;
use App\Livewire\Watchlist;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class WatchlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_toggle_the_watchlist(): void
    {
        $movie = Movie::factory()->create();

        Livewire::test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->call('toggleWatchlist')
            ->assertForbidden();

        $this->assertDatabaseCount('watchlist', 0);
    }

    public function test_user_can_add_a_movie_to_the_watchlist(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Livewire::actingAs($user)
            ->test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->assertSet('inWatchlist', false)
            ->call('toggleWatchlist')
            ->assertSet('inWatchlist', true);

        $this->assertDatabaseHas('watchlist', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_toggling_again_removes_the_movie_from_the_watchlist(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        $user->watchlistMovies()->attach($movie);

        Livewire::actingAs($user)
            ->test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->assertSet('inWatchlist', true)
            ->call('toggleWatchlist')
            ->assertSet('inWatchlist', false);

        $this->assertDatabaseMissing('watchlist', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_dashboard_lists_only_the_users_own_watchlist(): void
    {
        $user = User::factory()->create();
        $mine = Movie::factory()->create(['title' => 'My Movie']);
        $user->watchlistMovies()->attach($mine);

        $other = User::factory()->create();
        $theirs = Movie::factory()->create(['title' => 'Their Movie']);
        $other->watchlistMovies()->attach($theirs);

        Livewire::actingAs($user)
            ->test(Watchlist::class)
            ->assertSee('My Movie')
            ->assertDontSee('Their Movie');
    }

    public function test_user_can_remove_a_movie_from_the_dashboard(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['title' => 'Removable Movie']);
        $user->watchlistMovies()->attach($movie);

        Livewire::actingAs($user)
            ->test(Watchlist::class)
            ->assertSee('Removable Movie')
            ->call('remove', $movie->id)
            ->assertDontSee('Removable Movie');

        $this->assertDatabaseMissing('watchlist', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
