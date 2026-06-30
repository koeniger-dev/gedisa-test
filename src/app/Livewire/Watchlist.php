<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Watchlist extends Component
{
    /**
     * Remove a movie from the current user's watchlist.
     */
    public function remove(int $movieId): void
    {
        abort_unless(Auth::check(), 403);

        Auth::user()->watchlistMovies()->detach($movieId);
    }

    public function render(): View
    {
        $movies = Auth::user()
            ->watchlistMovies()
            // Same SQL-side aggregation as the public list — no N+1.
            ->withAvg('ratings as average_rating', 'rating')
            ->withCount('ratings as ratings_count')
            ->orderByDesc('watchlist.created_at')
            ->get();

        return view('livewire.watchlist', ['movies' => $movies]);
    }
}
