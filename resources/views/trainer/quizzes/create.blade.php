@extends('trainer.layouts.app', ['title' => 'Create Quiz | MCARE Trainer'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'quiz' => null,
        'formAction' => route('trainer.quizzes.store'),
        'formMethod' => 'POST',
        'pageTitle' => 'Create new quiz',
        'pageDescription' => 'Design an in-module assessment, set time limits, and configure questions.',
        'submitLabel' => 'Save & publish quiz',
    ])
@endsection
