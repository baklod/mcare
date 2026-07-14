@extends('trainee.layouts.app', ['title' => 'Schedule | MCARE Trainee'])

@section('content')
@php
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
    $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
@endphp
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6"><p class="dashboard-section-kicker">My schedule</p><h1 class="dashboard-section-title mt-2 text-3xl">Class schedule and notices</h1></header>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-2xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="font-bold text-slate-900">{{ $announcement->title }}</p><p class="mt-2 text-sm leading-6 text-slate-600">{{ $announcement->message }}</p><p class="mt-2 text-xs font-bold text-amber-700">{{ $announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted' }}</p></article>
                @empty
                    <p class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600">No class announcements yet.</p>
                @endforelse
            </div>
        </aside>
        <div class="dashboard-panel">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-5"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Class</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $application->schedule_preference }}</p></div>
                <div class="rounded-xl bg-slate-50 p-5 md:col-span-2"><p class="text-xs font-black uppercase tracking-wide text-slate-500">Schedule</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $scheduleLabel }}</p><p class="mt-2 text-sm text-slate-500">{{ $roomLabel }}</p></div>
                <div class="rounded-xl bg-purple-50 p-5 ring-1 ring-purple-100 md:col-span-3"><p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p></div>
            </div>
        </div>
    </div>
</section>
@endsection
