@php
    $commentLayout = match (auth()->user()?->role) {
        'admin' => 'admin.layouts.app',
        'trainer' => 'trainer.layouts.app',
        default => 'trainee.layouts.app',
    };
@endphp

@extends($commentLayout, ['title' => 'Classroom Comments | MCARE'])

@section('content')
<div class="lms-page">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classroom conversation</p>
            <h1>{{ $commentable->title }}</h1>
            <p>Review class discussion and permission-scoped private feedback.</p>
        </div>
        <a href="{{ $returnUrl }}" class="secondary-action">Back to classwork</a>
    </header>

    <x-classroom-comments :commentable="$commentable" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
</div>
@endsection
