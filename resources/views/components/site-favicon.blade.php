@props(['settings' => null])
@php
    $savedFavicon = $settings?->favicon;
    $faviconPath = is_string($savedFavicon) && filled($savedFavicon) && is_file(public_path($savedFavicon))
        ? $savedFavicon
        : 'favicon.ico';
    $faviconVersion = filemtime(public_path($faviconPath));
@endphp
<link rel="icon" href="{{ asset($faviconPath) }}?v={{ $faviconVersion }}">
