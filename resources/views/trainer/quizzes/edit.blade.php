@extends('trainer.layouts.app', ['title' => 'Edit Quiz | MCARE Trainer'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'formAction' => route('trainer.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Revise the instructions, availability, questions, and publication state.',
        'submitLabel' => 'Save changes',
    ])
@endsection
