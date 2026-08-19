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
    <header class="border-b border-slate-200 pb-6"><p class="dashboard-section-kicker">My documents</p><h1 class="dashboard-section-title mt-2 text-3xl">Training and registration records</h1></header>

    <article class="dashboard-panel">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <p class="dashboard-section-kicker">Certificate of Training Completion</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Caregiving NC II COTC</h2>
                @if($cotc)
                    <p class="mt-2 text-sm text-slate-600">Document {{ $cotc->document_number }} · {{ str($cotc->status)->headline() }}</p>
                @else
                    <p class="mt-2 text-sm text-slate-600">The admin will release this after every completion check passes.</p>
                @endif
            </div>
            <div class="shrink-0">
                @if($cotc?->isDownloadableByTrainee())
                    <a class="primary-action" href="{{ route('trainee.cotc.download', $cotc) }}">Download COTC once</a>
                @elseif($cotc?->downloaded_at)
                    <span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">Downloaded {{ $cotc->downloaded_at->format('M j, Y g:i A') }}</span>
                @elseif($cotc)
                    <span class="dashboard-pill bg-amber-50 text-amber-700 ring-amber-100">{{ str($cotc->status)->headline() }}</span>
                @else
                    <span class="dashboard-pill bg-slate-100 text-slate-600 ring-slate-200">Not issued</span>
                @endif
            </div>
        </div>
        <div class="mt-5 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($completionEligibility['checks'] as $check)
                <div class="flex items-start gap-3 border border-slate-200 p-3">
                    <span class="mt-0.5 text-xs font-black {{ $check['passed'] ? 'text-emerald-600' : 'text-slate-400' }}">{{ $check['passed'] ? 'PASS' : 'WAIT' }}</span>
                    <span><span class="block text-sm font-bold text-slate-900">{{ $check['label'] }}</span><span class="mt-1 block text-xs text-slate-500">{{ $check['detail'] }}</span></span>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-xs text-slate-500">For security, the trainee download can be claimed once. An interrupted or lost copy requires an admin reissue with a recorded reason.</p>
    </article>

    <div><p class="dashboard-section-kicker">Enrollment files</p><h2 class="mt-2 text-xl font-bold text-slate-950">Submitted registration files</h2></div>
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
