@props([
    'links' => [],
    'variant' => 'discover',
])

@php
    $visible = collect(['facebook', 'instagram', 'youtube'])
        ->filter(fn (string $network) => filled($links[$network] ?? null))
        ->values();
    $linkClass = $variant === 'footer'
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/50 bg-white/85 text-purple-700 shadow-sm hover:bg-white'
        : 'inline-flex h-11 w-11 items-center justify-center rounded-lg border border-purple-100 bg-white text-purple-700 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 sm:h-12 sm:w-12';
    $labels = [
        'facebook' => $variant === 'footer' ? 'Open MCARE Facebook page from footer' : 'Open MCARE Facebook page',
        'instagram' => $variant === 'footer' ? 'Open MCARE Instagram page from footer' : 'Open MCARE Instagram page',
        'youtube' => $variant === 'footer' ? 'Open MCARE YouTube channel from footer' : 'Open MCARE YouTube channel',
    ];
@endphp

@if ($visible->isNotEmpty())
    <div {{ $attributes->class('flex flex-wrap items-center justify-center gap-2.5 sm:gap-3') }}>
        @foreach ($visible as $network)
            <a href="{{ $links[$network] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $labels[$network] }}" class="{{ $linkClass }}">
                @if ($network === 'facebook')
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true">
                        <path d="M14 8.5h2V5.2c-.35-.05-1.55-.15-2.95-.15-2.9 0-4.9 1.82-4.9 5.18v2.92H5v3.7h3.15V24h3.88v-7.15h3.03l.48-3.7h-3.51v-2.55c0-1.07.29-2.1 1.97-2.1Z"/>
                    </svg>
                @elseif ($network === 'instagram')
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="16.8" cy="7.2" r="1" fill="currentColor"/>
                    </svg>
                @else
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                        <path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.65 4.6 12 4.6 12 4.6s-5.65 0-7.5.5a3 3 0 0 0-2.1 2.1A31.2 31.2 0 0 0 1.9 12c0 1.6.16 3.2.5 4.8a3 3 0 0 0 2.1 2.1c1.85.5 7.5.5 7.5.5s5.65 0 7.5-.5a3 3 0 0 0 2.1-2.1c.34-1.6.5-3.2.5-4.8 0-1.6-.16-3.2-.5-4.8ZM10 15.4V8.6l5.8 3.4L10 15.4Z"/>
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
@endif
