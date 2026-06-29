<?php

namespace App\Providers;

use App\Services\OmdbService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OmdbService::class, function (): OmdbService {
            /** @var array{key: ?string, url: string, cache_ttl: int} $config */
            $config = config('services.omdb');

            return new OmdbService(
                apiKey: (string) $config['key'],
                baseUrl: $config['url'],
                cacheTtl: $config['cache_ttl'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
