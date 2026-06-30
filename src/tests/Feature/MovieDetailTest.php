<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\MovieDetail;
use App\Models\Movie;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class MovieDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeOmdbDetail(array $overrides = []): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response(array_merge([
                'Response' => 'True',
                'Title' => 'Batman Begins',
                'Year' => '2005',
                'Type' => 'movie',
                'imdbID' => 'tt0372784',
                'Poster' => 'https://img/1.jpg',
                'Plot' => 'Bruce Wayne becomes Batman.',
                'Director' => 'Christopher Nolan',
                'Actors' => 'Christian Bale, Michael Caine',
            ], $overrides)),
        ]);
    }

    public function test_first_visit_fetches_and_caches_the_movie_locally(): void
    {
        $this->fakeOmdbDetail();
        $this->assertDatabaseMissing('movies', ['imdb_id' => 'tt0372784']);

        $this->get('/movies/tt0372784')
            ->assertOk()
            ->assertSee('Batman Begins')
            ->assertSee('Christopher Nolan');

        $this->assertDatabaseHas('movies', ['imdb_id' => 'tt0372784', 'title' => 'Batman Begins']);
        Http::assertSentCount(1);
    }

    public function test_second_visit_is_served_from_local_cache_without_calling_omdb(): void
    {
        Movie::factory()->create(['imdb_id' => 'tt0372784', 'title' => 'Cached Title']);
        Http::fake();

        $this->get('/movies/tt0372784')
            ->assertOk()
            ->assertSee('Cached Title');

        Http::assertNothingSent();
    }

    public function test_unknown_movie_returns_404(): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response(['Response' => 'False', 'Error' => 'Incorrect IMDb ID.']),
        ]);

        $this->get('/movies/tt0000000')->assertNotFound();
    }

    public function test_guest_cannot_rate(): void
    {
        $movie = Movie::factory()->create();

        Livewire::test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->call('rate', 4)
            ->assertForbidden();

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_authenticated_user_can_rate_a_movie(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Livewire::actingAs($user)
            ->test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->call('rate', 4)
            ->assertSet('userRating', 4);

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => 4,
        ]);
    }

    public function test_rating_again_updates_the_existing_rating_without_duplicating(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();
        Rating::factory()->for($user)->for($movie)->create(['rating' => 2]);

        Livewire::actingAs($user)
            ->test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->assertSet('userRating', 2)
            ->call('rate', 5)
            ->assertSet('userRating', 5);

        $this->assertDatabaseCount('ratings', 1);
        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'movie_id' => $movie->id,
            'rating' => 5,
        ]);
    }

    public function test_average_rating_reflects_all_votes(): void
    {
        $movie = Movie::factory()->create();
        Rating::factory()->for($movie)->create(['rating' => 3]);
        Rating::factory()->for($movie)->create(['rating' => 4]);

        Livewire::test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->assertSee('3.5')
            ->assertSee('2 Bewertungen');
    }

    public function test_rating_out_of_range_is_rejected(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create();

        Livewire::actingAs($user)
            ->test(MovieDetail::class, ['imdbId' => $movie->imdb_id])
            ->call('rate', 6)
            ->assertHasErrors('rating');

        $this->assertDatabaseCount('ratings', 0);
    }
}
