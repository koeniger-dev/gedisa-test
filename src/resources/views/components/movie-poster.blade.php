@props(['url' => '', 'alt' => ''])

{{--
    Poster with a graceful fallback. Two failure modes collapse into the same
    "Kein Poster" placeholder:
      1. no URL from OMDb (handled server-side: failed starts true),
      2. a present but broken/404 URL (handled client-side via the <img> error
         event — no server-side HEAD checks, the browser fetch is reused).
--}}
<div x-data="{ failed: @js($url === '') }" {{ $attributes->class('w-full bg-gray-100') }}>
    @if ($url !== '')
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            x-show="!failed"
            x-on:error="failed = true"
            class="h-full w-full object-cover"
        >
    @endif

    <div
        x-show="failed"
        @if ($url !== '') style="display: none;" @endif
        class="h-full w-full flex items-center justify-center text-gray-400 text-sm"
    >
        Kein Poster
    </div>
</div>
