@php
    $faviconVersion = @filemtime(public_path('favicon.png')) ?: time();
    $favicon16 = asset('favicon-16.png').'?v='.$faviconVersion;
    $favicon32 = asset('favicon-32.png').'?v='.$faviconVersion;
    $faviconPng = asset('favicon.png').'?v='.$faviconVersion;
    $faviconLarge = asset('assets/images/favicon-180.png').'?v='.$faviconVersion;
    $faviconIco = asset('favicon.ico').'?v='.$faviconVersion;
@endphp
<link rel="icon" href="{{ $favicon16 }}" type="image/png" sizes="16x16">
<link rel="icon" href="{{ $favicon32 }}" type="image/png" sizes="32x32">
<link rel="icon" href="{{ $faviconPng }}" type="image/png" sizes="48x48">
<link rel="icon" href="{{ $faviconIco }}" sizes="any">
<link rel="apple-touch-icon" href="{{ $faviconLarge }}">
