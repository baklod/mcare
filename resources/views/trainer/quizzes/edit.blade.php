@extends('trainer.layouts.app', ['title' => 'Edit Quiz | MCARE Trainer'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'formAction' => route('trainer.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Revise the instructions, availability, questions, and publication state.',
        'submitLabel' => 'Save changes',
    ])

    <div class="mx-auto mt-6 max-w-7xl">
        <x-classroom-comments :commentable="$quiz" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
    </div>
@endsection
