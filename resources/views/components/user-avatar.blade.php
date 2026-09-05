@props([
    'user' => null,
    'name' => null,
    'src' => null,
    'application' => null,
    'useEnrollmentPhoto' => false,
])

@php
    $resolvedUser = $user ?: $application?->user;
    $displayName = trim((string) ($name ?: $resolvedUser?->name ?: $resolvedUser?->email ?: 'MCARE User'));
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($displayName, 0, 1));
    $enrollmentPhotoUrl = (
        $useEnrollmentPhoto
        && $resolvedUser
        && filled($application?->id_photo_path)
        && auth()->user()?->role === 'admin'
    ) ? route('admin.accounts.photo', $resolvedUser, absolute: false) : '';

    $candidateUrl = trim((string) ($src ?: $enrollmentPhotoUrl ?: $resolvedUser?->profilePhotoUrl() ?? ''));

    $isSafeRelativeUrl = \Illuminate\Support\Str::startsWith($candidateUrl, '/')
        && ! \Illuminate\Support\Str::startsWith($candidateUrl, '//');
    $isSafeRemoteUrl = filter_var($candidateUrl, FILTER_VALIDATE_URL)
        && strtolower((string) parse_url($candidateUrl, PHP_URL_SCHEME)) === 'https';
    $avatarUrl = $isSafeRelativeUrl || $isSafeRemoteUrl ? $candidateUrl : null;
@endphp

<span {{ $attributes->class(['user-avatar'])->merge(['title' => $displayName]) }}>
    <span class="user-avatar-fallback" aria-hidden="true">{{ $initial ?: 'M' }}</span>
    @if ($avatarUrl)
        <img
            src="{{ $avatarUrl }}"
            alt=""
            class="user-avatar-image"
            loading="lazy"
            decoding="async"
            referrerpolicy="no-referrer"
            data-user-avatar-image
        >
    @endif
</span>
