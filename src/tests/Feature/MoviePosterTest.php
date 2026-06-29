<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class MoviePosterTest extends TestCase
{
    public function test_renders_image_with_client_side_error_fallback_when_url_present(): void
    {
        $this->blade('<x-movie-poster url="https://img/poster.jpg" alt="Batman" />')
            ->assertSee('https://img/poster.jpg', false)
            ->assertSee('x-on:error="failed = true"', false)
            ->assertSee('Kein Poster');
    }

    public function test_renders_only_placeholder_when_url_is_empty(): void
    {
        $this->blade('<x-movie-poster url="" alt="Batman" />')
            ->assertDontSee('<img', false)
            ->assertSee('failed: true', false)
            ->assertSee('Kein Poster');
    }
}
