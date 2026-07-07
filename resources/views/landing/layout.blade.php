<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $content['meta']['title'] }}</title>
    <meta name="description" content="{{ $content['meta']['description'] }}">
    <meta name="keywords" content="{{ $content['meta']['keywords'] }}">
    <meta property="og:title" content="{{ $content['meta']['title'] }}">
    <meta property="og:description" content="{{ $content['meta']['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset($content['meta']['og_image']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset(config('branding.favicon')) }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset(config('branding.icon')) }}">
    <meta name="theme-color" content="#6366f1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    @php
        $manifestPath = public_path('build/manifest.json');
        $landingManifest = file_exists($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true)
            : [];
        $landingCss = $landingManifest['resources/css/landing.css']['file'] ?? null;
        $landingJs = $landingManifest['resources/js/landing.js']['file'] ?? null;
        $schemaOrg = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => config('branding.name'),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => $content['meta']['description'],
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        ];
    @endphp
    @if ($landingCss)
        <link rel="stylesheet" href="{{ asset('build/'.$landingCss) }}">
    @else
        @vite(['resources/css/landing.css'])
    @endif
    @if ($landingJs)
        <script type="module" src="{{ asset('build/'.$landingJs) }}"></script>
    @else
        @vite(['resources/js/landing.js'])
    @endif
    <script type="application/ld+json">
        {!! json_encode($schemaOrg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="landing-page">
    @yield('content')
</body>
</html>
