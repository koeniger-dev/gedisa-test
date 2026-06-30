<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\RatedMoviesList;
use App\Models\Movie;
use App\Models\Rating;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class RatedMoviesListTest extends TestCase
{
    use RefreshDatabase;

    public function test_rated_list_is_publicly_accessible_for_guests(): void
    {
        $this->get('/rated')
            ->assertOk()
            ->assertSeeLivewire(RatedMoviesList::class);
    }

    public function test_only_rated_movies_are_listed(): void
    {
        $rated = Movie::factory()->create(['title' => 'Rated Movie']);
        Rating::factory()->for($rated)->create(['rating' => 5]);

        Movie::factory()->create(['title' => 'Unrated Movie']);

        Livewire::test(RatedMoviesList::class)
            ->assertSee('Rated Movie')
            ->assertDontSee('Unrated Movie');
    }

    public function test_shows_average_and_count(): void
    {
        $movie = Movie::factory()->create(['title' => 'Average Movie']);
        Rating::factory()->for($movie)->create(['rating' => 4]);
        Rating::factory()->for($movie)->create(['rating' => 5]);

        Livewire::test(RatedMoviesList::class)
            ->assertSee('4.5')
            ->assertSee('(2)');
    }

    public function test_best_rated_movies_are_listed_first_by_default(): void
    {
        $low = Movie::factory()->create(['title' => 'Low Rated']);
        Rating::factory()->for($low)->create(['rating' => 1]);

        $high = Movie::factory()->create(['title' => 'High Rated']);
        Rating::factory()->for($high)->create(['rating' => 5]);

        $this->get('/rated')->assertSeeInOrder(['High Rated', 'Low Rated']);
    }

    public function test_popular_sort_orders_by_rating_count(): void
    {
        $few = Movie::factory()->create(['title' => 'Few Ratings']);
        Rating::factory()->for($few)->create(['rating' => 5]);

        $many = Movie::factory()->create(['title' => 'Many Ratings']);
        Rating::factory()->count(3)->for($many)->create(['rating' => 3]);

        Livewire::test(RatedMoviesList::class)
            ->set('sort', 'popular')
            ->assertSeeInOrder(['Many Ratings', 'Few Ratings']);
    }

    public function test_empty_state_when_no_movie_is_rated(): void
    {
        Movie::factory()->create(['title' => 'Unrated Movie']);

        Livewire::test(RatedMoviesList::class)
            ->assertSee('noch keine Filme bewertet')
            ->assertDontSee('Unrated Movie');
    }
}
