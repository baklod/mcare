@extends('trainer.layouts.app', ['title' => 'Create Quiz | MCARE Trainer'])

@section('content')
    @include('trainer.quizzes.partials.form', [
        'formAction' => route('trainer.quizzes.store'),
        'formMethod' => 'POST',
        'pageTitle' => 'Create quiz',
        'pageDescription' => 'Build a focused assessment that works cleanly on phones and laptops.',
        'submitLabel' => 'Save quiz',
    ])
@endsection
