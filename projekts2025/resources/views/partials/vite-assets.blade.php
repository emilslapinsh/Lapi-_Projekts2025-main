<?php // Assets: built files from /build + Tailwind CDN fallback for hosting ?>
@if (app()->environment('local') && file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    @php
        $manifestPath = public_path('build/manifest.json');
        $cssFile = null;
        $jsFile = null;
        if (is_readable($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
            $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
            $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
        }
    @endphp
    @if ($cssFile)
        <link rel="stylesheet" href="/build/{{ $cssFile }}" />
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    @if ($jsFile)
        <script type="module" src="/build/{{ $jsFile }}"></script>
    @endif
@endif
