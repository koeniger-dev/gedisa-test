<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\FindOrCacheMovie;
use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class FindOrCacheMovieTest extends TestCase
{
    use RefreshDatabase;

    private function action(): FindOrCacheMovie
    {
        return app(FindOrCacheMovie::class);
    }

    public function test_returns_locally_cached_movie_without_calling_omdb(): void
    {
        $movie = Movie::factory()->create(['imdb_id' => 'tt0372784']);
        Http::fake();

        $result = ($this->action())('tt0372784');

        $this->assertTrue($result->is($movie));
        Http::assertNothingSent();
    }

    public function test_fetches_and_persists_movie_on_cache_miss(): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response([
                'Response' => 'True',
                'Title' => 'Batman Begins',
                'Year' => '2005',
                'Type' => 'movie',
                'imdbID' => 'tt0372784',
                'Poster' => 'https://img/1.jpg',
                'Plot' => 'Bruce Wayne becomes Batman.',
                'Director' => 'Christopher Nolan',
                'Actors' => 'Christian Bale',
            ]),
        ]);
        $this->assertDatabaseMissing('movies', ['imdb_id' => 'tt0372784']);

        $result = ($this->action())('tt0372784');

        $this->assertInstanceOf(Movie::class, $result);
        $this->assertSame('Batman Begins', $result->title);
        $this->assertDatabaseHas('movies', [
            'imdb_id' => 'tt0372784',
            'director' => 'Christopher Nolan',
        ]);
        Http::assertSentCount(1);
    }

    public function test_returns_null_when_movie_is_unknown_to_omdb(): void
    {
        Http::fake([
            'www.omdbapi.com/*' => Http::response(['Response' => 'False', 'Error' => 'Incorrect IMDb ID.']),
        ]);

        $result = ($this->action())('tt0000000');

        $this->assertNull($result);
        $this->assertDatabaseCount('movies', 0);
    }
}
