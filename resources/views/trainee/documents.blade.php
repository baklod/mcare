@extends('trainee.layouts.app', ['title' => 'Documents | MCARE Trainee'])

@section('content')
@php
    $documents = [
        'birth-certificate' => ['label' => 'Birth Certificate', 'path' => $application->birth_certificate_path],
        'education-document' => ['label' => 'Form 137/138 or Diploma', 'path' => $application->education_document_path],
        'good-moral-certificate' => ['label' => 'Good Moral Certificate', 'path' => $application->good_moral_certificate_path],
        'id-photo' => ['label' => 'ID Photo', 'path' => $application->id_photo_path],
        'signature' => ['label' => 'E-Signature', 'path' => $application->signature_path],
    ];
@endphp
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6"><p class="dashboard-section-kicker">My documents</p><h1 class="dashboard-section-title mt-2 text-3xl">Submitted registration files</h1><p class="mt-2 text-sm text-slate-600">Review the status and admin feedback for each enrollment document.</p></header>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($documents as $documentKey => $document)
            @php
                $feedback = data_get($application->document_review, $documentKey, []);
            @endphp
            <article class="dashboard-card p-5">
                <p class="font-bold text-slate-900">{{ $document['label'] }}</p>
                <span class="dashboard-pill mt-4 {{ $document['path'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-red-50 text-red-700 ring-red-100' }}">{{ $document['path'] ? 'On file' : 'Missing' }}</span>
                @if ($feedback)
                    <p class="mt-4 text-xs font-black uppercase {{ ($feedback['status'] ?? '') === 'accepted' ? 'text-emerald-700' : 'text-amber-700' }}">{{ str($feedback['status'] ?? 'unreviewed')->headline() }}</p>
                    @if ($feedback['note'] ?? null)<p class="mt-2 text-sm leading-6 text-slate-600">{{ $feedback['note'] }}</p>@endif
                @endif
            </article>
        @endforeach
    </div>
</section>
@endsection
