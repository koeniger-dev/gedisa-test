<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Movie;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class RatedMoviesList extends Component
{
    use WithPagination;

    /**
     * Active sort order, mirrored into the URL (?sort=) for shareable links.
     */
    #[Url(as: 'sort', except: 'best')]
    public string $sort = 'best';

    /**
     * Using hook to reset to page 1 whenever the sort changes, so the user never lands on a
     * now-out-of-range page.
     */
    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Movie::query()
            // Public list shows only movies that have actually been rated.
            ->whereHas('ratings')
            // Average and count computed in SQL (subqueries) — no N+1, no PHP-side
            // averaging, and a single paginated query for the whole page.
            ->withAvg('ratings as average_rating', 'rating')
            ->withCount('ratings as ratings_count');

        match ($this->sort) {
            'popular' => $query->orderByDesc('ratings_count')->orderByDesc('average_rating'),
            'recent' => $query->latest(),
            default => $query->orderByDesc('average_rating')->orderByDesc('ratings_count'),
        };

        return view('livewire.rated-movies-list', [
            'movies' => $query->paginate(12),
        ]);
    }
}
