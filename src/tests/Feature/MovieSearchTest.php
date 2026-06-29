<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\MovieSearch;
use App\Models\Movie;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

final class MovieSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, string>>  $search
     */
    private function fakeOmdbSearch(array $search): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response([
                'Response' => 'True',
                'Search' => $search,
            ]),
        ]);
    }

    public function test_homepage_renders_the_search_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeLivewire(MovieSearch::class);
    }

    public function test_short_query_does_not_hit_the_api(): void
    {
        Http::fake();

        Livewire::test(MovieSearch::class)
            ->set('query', 'a')
            ->assertViewHas('results', fn ($results): bool => $results->isEmpty());

        Http::assertNothingSent();
    }

    public function test_search_renders_omdb_results(): void
    {
        $this->fakeOmdbSearch([
            ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0372784', 'Type' => 'movie', 'Poster' => 'https://img/1.jpg'],
        ]);

        Livewire::test(MovieSearch::class)
            ->set('query', 'batman')
            ->assertViewHas('results', fn ($results): bool => $results->count() === 1)
            ->assertSee('Batman Begins')
            ->assertSee('2005');
    }

    public function test_results_are_enriched_with_local_average_rating(): void
    {
        // Locally cached movie with two ratings -> average 4.5.
        $movie = Movie::factory()->create(['imdb_id' => 'tt0372784']);
        Rating::factory()->for($movie)->create(['rating' => 4]);
        Rating::factory()->for($movie)->create(['rating' => 5]);

        $this->fakeOmdbSearch([
            ['Title' => 'Batman Begins', 'Year' => '2005', 'imdbID' => 'tt0372784', 'Type' => 'movie', 'Poster' => 'https://img/1.jpg'],
        ]);

        Livewire::test(MovieSearch::class)
            ->set('query', 'batman')
            ->assertSee('4.5')
            ->assertSee('(2)');
    }

    public function test_movie_without_local_ratings_shows_no_average(): void
    {
        $this->fakeOmdbSearch([
            ['Title' => 'Some Unrated Movie', 'Year' => '1999', 'imdbID' => 'tt9999999', 'Type' => 'movie', 'Poster' => 'N/A'],
        ]);

        Livewire::test(MovieSearch::class)
            ->set('query', 'unrated')
            ->assertSee('Noch keine Bewertung');
    }
}
