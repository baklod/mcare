@props([
    'src',
    'size' => 'viewer',
])

@php
    $frame = $size === 'page'
        ? 'h-[72%] w-[72%] max-h-[42rem] max-w-[42rem]'
        : 'h-[72%] w-[72%] max-h-[36rem] max-w-[36rem]';
@endphp

<div {{ $attributes->class(['learning-watermark', $frame]) }} aria-hidden="true">
    <img src="{{ $src }}" alt="" class="learning-watermark__logo" draggable="false">
</div>
