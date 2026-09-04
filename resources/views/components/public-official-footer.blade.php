@props([
    'note' => 'Official public information. Applicant records are processed for training, scholarship, employment, and related institutional purposes.',
])

<footer {{ $attributes->class('auth-footer') }}>
    <div class="auth-footer-inner">
        <p>&copy; {{ date('Y') }} Mission Care Training and Assessment Center.</p>
        <p>TESDA-accredited Caregiving NC II training and assessment.</p>
        <p class="landing-colophon-note">{{ $note }}</p>
    </div>
</footer>
