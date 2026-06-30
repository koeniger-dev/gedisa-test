<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <a href="{{ route('movies.search') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-900">
        &larr; Zur Suche
    </a>

    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-8">
        {{-- Poster (reuses the graceful-fallback component from the search view). --}}
        <div class="sm:col-span-1">
            <x-movie-poster :url="$movie->poster_url" :alt="$movie->title" class="h-96 rounded-lg ring-1 ring-gray-200" />
        </div>

        {{-- Metadata + rating --}}
        <div class="sm:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-semibold text-gray-900">{{ $movie->title }}</h1>
                <span class="shrink-0 mt-1 inline-flex items-center rounded-full bg-gray-100 px-3 py-0.5 text-xs font-medium text-gray-600 capitalize">
                    {{ $movie->type->value }}
                </span>
            </div>

            <p class="mt-1 text-sm text-gray-500">{{ $movie->year }}</p>

            {{-- Average rating (DB-side aggregation). --}}
            <div class="mt-4 flex items-center gap-2">
                @if ($this->ratingsCount > 0)
                    <span class="text-amber-500 text-lg">★</span>
                    <span class="font-medium text-gray-900">{{ number_format($this->averageRating, 1) }}</span>
                    <span class="text-sm text-gray-400">/ 5 · {{ $this->ratingsCount }} {{ $this->ratingsCount === 1 ? 'Bewertung' : 'Bewertungen' }}</span>
                @else
                    <span class="text-sm text-gray-400">Noch keine Bewertungen</span>
                @endif
            </div>

            @if ($movie->director !== '')
                <p class="mt-4 text-sm"><span class="text-gray-500">Regie:</span> <span class="text-gray-900">{{ $movie->director }}</span></p>
            @endif
            @if ($movie->actors !== '')
                <p class="mt-1 text-sm"><span class="text-gray-500">Besetzung:</span> <span class="text-gray-900">{{ $movie->actors }}</span></p>
            @endif
            @if ($movie->plot !== '')
                <p class="mt-4 text-gray-700 leading-relaxed">{{ $movie->plot }}</p>
            @endif

            {{-- Rating widget --}}
            <div class="mt-8 border-t border-gray-200 pt-6">
                @auth
                    <p class="text-sm font-medium text-gray-700">
                        {{ $userRating ? 'Deine Bewertung' : 'Jetzt bewerten' }}
                    </p>

                    <div
                        x-data="{ hover: 0 }"
                        class="mt-2 flex items-center gap-1"
                        wire:loading.class="opacity-50 pointer-events-none"
                        wire:target="rate"
                    >
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="rate({{ $i }})"
                                x-on:mouseenter="hover = {{ $i }}"
                                x-on:mouseleave="hover = 0"
                                x-bind:class="(hover || $wire.userRating) >= {{ $i }} ? 'text-amber-500' : 'text-gray-300'"
                                class="text-3xl leading-none transition focus:outline-none hover:scale-110"
                                aria-label="{{ $i }} {{ $i === 1 ? 'Stern' : 'Sterne' }}"
                            >★</button>
                        @endfor

                        @if ($userRating)
                            <span class="ml-2 text-sm text-gray-500">({{ $userRating }}/5)</span>
                        @endif
                    </div>

                    {{-- Watchlist toggle --}}
                    <button
                        type="button"
                        wire:click="toggleWatchlist"
                        wire:loading.attr="disabled"
                        wire:target="toggleWatchlist"
                        class="mt-4 inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium ring-1 transition
                            {{ $inWatchlist
                                ? 'bg-gray-900 text-white ring-gray-900 hover:bg-gray-700'
                                : 'bg-white text-gray-700 ring-gray-300 hover:ring-gray-900' }}"
                    >
                        {{ $inWatchlist ? '✓ In Watchlist' : '+ Zur Watchlist' }}
                    </button>
                @else
                    <p class="text-sm text-gray-500">
                        <a href="{{ route('login') }}" wire:navigate class="font-medium text-gray-900 underline">Logge dich ein</a>,
                        um diesen Film zu bewerten.
                    </p>
                @endauth
            </div>
        </div>
    </div>
</div>
