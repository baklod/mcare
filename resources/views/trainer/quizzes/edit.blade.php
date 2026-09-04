@extends('trainer.layouts.app', ['title' => 'Edit Quiz | MCARE Trainer'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'formAction' => route('trainer.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Review the quiz settings, then revise questions before saving.',
        'submitLabel' => 'Save changes',
    ])

    <div class="lms-quiz-editor-followup">
        <x-classroom-comments :commentable="$quiz" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
    </div>
@endsection
