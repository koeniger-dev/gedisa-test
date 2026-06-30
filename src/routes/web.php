<?php

use App\Livewire\Actions\Logout;
use App\Livewire\MovieDetail;
use App\Livewire\MovieSearch;
use App\Livewire\RatedMoviesList;
use Illuminate\Support\Facades\Route;

Route::get('/', MovieSearch::class)->name('movies.search');
Route::get('rated', RatedMoviesList::class)->name('movies.rated');
Route::get('movies/{imdbId}', MovieDetail::class)->name('movies.show');

// Logout lives in the shared layout nav; a plain POST keeps it decoupled from
// any single Livewire component (the old Breeze nav component was removed).
Route::post('logout', function (Logout $logout) {
    $logout();

    return redirect()->route('movies.search');
})->middleware('auth')->name('logout');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
