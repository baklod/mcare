@extends('trainee.layouts.app', ['title' => 'Class Stream | MCARE Trainee'])

@section('content')
@php
    $batchLabel = $application->batch
        ? $application->batch->name.' '.$application->batch->year
        : 'Caregiving NC II';
@endphp

<div class="lms-page" data-lms-stream data-lms-role="trainee">
    <header class="lms-class-banner">
        <div>
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1>{{ $batchLabel }}</h1>
            <p>Class announcements, reminders, and the next activities your trainer has shared.</p>
        </div>
        <span class="lms-banner-mark" aria-hidden="true">M</span>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainee classroom sections">
        <a href="{{ route('trainee.stream') }}" class="is-active" aria-current="page">Stream</a>
        <a href="{{ route('trainee.modules.index') }}">Classwork</a>
        <a href="{{ route('trainee.quizzes.index') }}">Quizzes</a>
        <a href="{{ route('trainee.schedule') }}">Calendar</a>
    </nav>

    <div class="lms-stream-layout">
        <main class="lms-stream-feed">
            @if(isset($adminAnnouncements) && $adminAnnouncements->isNotEmpty())
                <section class="space-y-4 mb-6" aria-label="Administrative notices">
                    @foreach($adminAnnouncements as $adminNotice)
                        <article class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50 via-white to-sky-50 p-5 shadow-xs">
                            <div class="flex items-start gap-3.5">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-700 text-white">
                                    <x-dashboard-icon :name="$adminNotice->kind === 'reminder' ? 'credit-card' : 'bullhorn'" class="h-5 w-5" />
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-md bg-purple-100 px-2 py-0.5 text-[11px] font-bold uppercase text-purple-800">
                                                {{ $adminNotice->kind === 'reminder' ? 'Payment / Due Date Reminder' : 'Administration Notice' }}
                                            </span>
                                            @if($adminNotice->due_date)
                                                <span class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-800">
                                                    Due: {{ $adminNotice->due_date->format('M d, Y') }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-xs text-slate-500">{{ $adminNotice->posted_at?->diffForHumans() ?? 'Recently' }}</span>
                                    </div>
                                    <h2 class="text-base font-bold text-slate-950">{{ $adminNotice->title }}</h2>
                                    <p class="text-sm text-slate-700 leading-relaxed">{{ $adminNotice->message }}</p>
                                    @if($adminNotice->kind === 'reminder')
                                        <div class="pt-2">
                                            <a href="{{ route('trainee.payments') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-700 hover:text-purple-900 underline">
                                                <span>Open Billing & Payment Dashboard</span>
                                                <x-dashboard-icon name="arrow-right" class="h-3 w-3" />
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif

            <section class="lms-post-list" aria-label="Class announcements">
                @forelse($announcements as $announcement)
                    @php
                        $postedAt = $announcement->posted_at ?? $announcement->created_at;
                        $postKind = str($announcement->kind ?? 'announcement')->headline();
                    @endphp
                    <article class="lms-post-card {{ $announcement->is_pinned ? 'is-pinned' : '' }}" data-announcement-card>
                        <header class="lms-post-header">
                            <span class="lms-avatar" aria-hidden="true">{{ strtoupper(substr($announcement->trainer?->name ?? 'M', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="lms-post-author-line">
                                    <strong>{{ $announcement->trainer?->name ?? 'MCARE Trainer' }}</strong>
                                    @if($announcement->is_pinned)
                                        <span class="lms-status-chip is-purple">Pinned</span>
                                    @endif
                                </div>
                                <p>{{ $postKind }} - {{ $postedAt?->diffForHumans() ?? 'Recently' }}</p>
                            </div>
                        </header>
                        <div class="lms-post-body">
                            <h2>{{ $announcement->title }}</h2>
                            <p>{{ $announcement->message }}</p>
                        </div>
                        @if($announcement->expires_at)
                            <footer class="lms-card-footer">
                                <span class="lms-muted-note">Visible until {{ $announcement->expires_at->format('M d, Y g:i A') }}</span>
                            </footer>
                        @endif
                    </article>
                @empty
                    <div class="lms-empty-state">
                        <x-dashboard-icon name="bell" />
                        <h2>No announcements yet</h2>
                        <p>Your trainer's class updates will appear in this stream.</p>
                    </div>
                @endforelse
            </section>

            @if(method_exists($announcements, 'hasPages') && $announcements->hasPages())
                <div class="lms-pagination">{{ $announcements->links() }}</div>
            @endif
        </main>

        <aside class="lms-stream-sidebar" aria-label="Upcoming classwork">
            <section class="lms-side-card">
                <div class="lms-side-card-heading">
                    <h2>Upcoming</h2>
                    <a href="{{ route('trainee.schedule') }}">View calendar</a>
                </div>
                <div class="lms-upcoming-list">
                    @forelse($upcomingQuizzes as $quiz)
                        <a href="{{ route('trainee.quizzes.show', $quiz) }}" class="lms-upcoming-item">
                            <span class="lms-upcoming-icon is-quiz"><x-dashboard-icon name="square-check" /></span>
                            <span><strong>{{ $quiz->title }}</strong><small>Quiz - {{ $quiz->due_at?->format('M d, g:i A') ?? 'No due date' }}</small></span>
                        </a>
                    @empty
                    @endforelse
                    @forelse($upcomingModules as $module)
                        <a href="{{ route('trainee.modules.show', $module) }}" class="lms-upcoming-item">
                            <span class="lms-upcoming-icon"><x-dashboard-icon name="book-open" /></span>
                            <span><strong>{{ $module->title }}</strong><small>Material - {{ $module->due_at?->format('M d, g:i A') ?? 'No due date' }}</small></span>
                        </a>
                    @empty
                    @endforelse

                    @if(collect($upcomingQuizzes)->isEmpty() && collect($upcomingModules)->isEmpty())
                        <p class="lms-empty-copy">Nothing is due soon. You're all caught up.</p>
                    @endif
                </div>
            </section>

            <section class="lms-side-card">
                <h2>Quick links</h2>
                <div class="lms-quick-links">
                    <a href="{{ route('trainee.modules.index') }}"><x-dashboard-icon name="book-open" /> Review classwork</a>
                    <a href="{{ route('trainee.quizzes.index') }}"><x-dashboard-icon name="square-check" /> Open quizzes</a>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
