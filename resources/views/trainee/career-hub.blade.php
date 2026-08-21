@extends('trainee.layouts.app', ['title' => 'Career Hub | MCARE Graduate'])

@section('content')
<section class="space-y-6">
    @include('trainee.partials.career-hub-content', ['isAdminPreview' => false])
</section>
@endsection
