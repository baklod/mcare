@extends('admin.layouts.app', ['title' => 'Career Hub Preview | MCARE'])

@section('content')
<section class="space-y-6">
    @include('trainee.partials.career-hub-content', ['isAdminPreview' => true, 'alumniProfile' => null])
</section>
@endsection
