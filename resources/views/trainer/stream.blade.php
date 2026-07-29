@extends('trainer.layouts.app', ['title' => 'Class Stream | MCARE Trainer'])

@section('content')
@php
    $selectedBatchId = (int) request('batch_id');
    $selectedBatch = $selectedBatchId ? $batches->firstWhere('id', $selectedBatchId) : null;
    $stats = array_merge([
        'total' => method_exists($announcements, 'total') ? $announcements->total() : collect($announcements)->count(),
        'published' => collect($announcements)->where('is_published', true)->count(),
        'scheduled' => 0,
    ], $streamStats ?? []);
@endphp

<div class="lms-page" data-lms-stream data-lms-role="trainer">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1>Class stream</h1>
            <p>Share class updates, news, and reminders in one familiar feed. Posts can be aimed at one batch or every active class.</p>
        </div>
        <a href="{{ route('trainer.resources') }}" class="secondary-action">Open classwork</a>
    </header>

    <nav class="lms-context-tabs" aria-label="Filter stream by class">
        <a href="{{ route('trainer.stream') }}" class="{{ $selectedBatchId === 0 ? 'is-active' : '' }}" @if($selectedBatchId === 0) aria-current="page" @endif>All classes</a>
        @foreach($batches as $batch)
            <a href="{{ route('trainer.stream', ['batch_id' => $batch->id]) }}" class="{{ $selectedBatchId === $batch->id ? 'is-active' : '' }}" @if($selectedBatchId === $batch->id) aria-current="page" @endif>
                {{ $batch->name }} {{ $batch->year }}
            </a>
        @endforeach
    </nav>

    <div class="lms-stream-layout">
        <main class="lms-stream-feed">
            @if($errors->any())
                <div class="lms-inline-alert is-danger" role="alert">
                    <strong>Check the announcement details.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <section class="lms-composer" data-announcement-composer aria-labelledby="announcement-composer-title">
                <div class="lms-composer-heading">
                    <span class="lms-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()?->name ?? 'T', 0, 1)) }}</span>
                    <div>
                        <p class="lms-eyebrow">New post</p>
                        <h2 id="announcement-composer-title">Share with your class</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('trainer.announcements.store') }}" class="lms-form-grid">
                    @csrf
                    <div class="lms-field">
                        <label for="announcement-batch">Class</label>
                        <select id="announcement-batch" name="training_batch_id">
                            <option value="">All active classes</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) old('training_batch_id', $selectedBatchId ?: '') === (string) $batch->id)>
                                    {{ $batch->name }} {{ $batch->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lms-field">
                        <label for="announcement-kind">Post type</label>
                        <select id="announcement-kind" name="kind">
                            @foreach(['announcement' => 'Announcement', 'news' => 'Class news', 'reminder' => 'Reminder'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('kind', 'announcement') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lms-field lms-field-wide">
                        <label for="announcement-title">Title</label>
                        <input id="announcement-title" name="title" value="{{ old('title') }}" maxlength="160" required placeholder="Example: Skills demonstration moved to Room 202">
                    </div>
                    <div class="lms-field lms-field-wide">
                        <label for="announcement-message">Message</label>
                        <textarea id="announcement-message" name="message" rows="4" maxlength="3000" required placeholder="Write the information trainees need to know.">{{ old('message') }}</textarea>
                    </div>
                    <input type="hidden" name="audience" value="trainees">
                    <div class="lms-form-options lms-field-wide">
                        <label class="lms-check">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned'))>
                            <span>Pin to the top</span>
                        </label>
                        <label class="lms-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                            <span>Publish or schedule</span>
                        </label>
                        <div class="lms-field lms-compact-field">
                            <label for="announcement-posted-at">Post at</label>
                            <input id="announcement-posted-at" type="datetime-local" name="posted_at" value="{{ old('posted_at') }}">
                        </div>
                        <div class="lms-field lms-compact-field">
                            <label for="announcement-expires">Hide after</label>
                            <input id="announcement-expires" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
                        </div>
                    </div>
                    <div class="lms-form-actions lms-field-wide">
                        <button class="primary-action">Post announcement</button>
                    </div>
                </form>
            </section>

            <section class="lms-post-list" aria-label="Class announcements">
                @forelse($announcements as $announcement)
                    @php
                        $postedAt = $announcement->posted_at ?? $announcement->created_at;
                        $postKind = str($announcement->kind ?? 'announcement')->headline();
                    @endphp
                    <article class="lms-post-card {{ $announcement->is_pinned ? 'is-pinned' : '' }}" data-announcement-card>
                        <header class="lms-post-header">
                            <span class="lms-avatar" aria-hidden="true">{{ strtoupper(substr($announcement->trainer?->name ?? auth()->user()?->name ?? 'T', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="lms-post-author-line">
                                    <strong>{{ $announcement->trainer?->name ?? auth()->user()?->name ?? 'MCARE Trainer' }}</strong>
                                    @if($announcement->is_pinned)
                                        <span class="lms-status-chip is-purple">Pinned</span>
                                    @endif
                                </div>
                                <p>{{ $postKind }} - {{ $announcement->batch ? $announcement->batch->name.' '.$announcement->batch->year : 'All active classes' }} - {{ $postedAt?->diffForHumans() ?? 'Draft' }}</p>
                            </div>
                        </header>

                        <div class="lms-post-body">
                            <h2>{{ $announcement->title }}</h2>
                            <p>{{ $announcement->message }}</p>
                        </div>

                        <footer class="lms-card-footer">
                            <span class="lms-status-chip {{ $announcement->is_published ? 'is-green' : 'is-neutral' }}">
                                {{ $announcement->is_published ? 'Published' : 'Draft' }}
                            </span>
                            <div class="lms-card-actions">
                                <details class="lms-inline-editor" data-announcement-editor>
                                    <summary>Edit</summary>
                                    <form method="POST" action="{{ route('trainer.announcements.update', $announcement) }}" class="lms-form-grid">
                                        @csrf
                                        @method('PATCH')
                                        <div class="lms-field">
                                            <label for="announcement-{{ $announcement->id }}-batch">Class</label>
                                            <select id="announcement-{{ $announcement->id }}-batch" name="training_batch_id">
                                                <option value="">All active classes</option>
                                                @foreach($batches as $batch)
                                                    <option value="{{ $batch->id }}" @selected((int) $announcement->training_batch_id === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="lms-field">
                                            <label for="announcement-{{ $announcement->id }}-kind">Post type</label>
                                            <select id="announcement-{{ $announcement->id }}-kind" name="kind">
                                                @foreach(['announcement' => 'Announcement', 'news' => 'Class news', 'reminder' => 'Reminder'] as $value => $label)
                                                    <option value="{{ $value }}" @selected(($announcement->kind ?? 'announcement') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="lms-field lms-field-wide">
                                            <label for="announcement-{{ $announcement->id }}-title">Title</label>
                                            <input id="announcement-{{ $announcement->id }}-title" name="title" value="{{ $announcement->title }}" maxlength="160" required>
                                        </div>
                                        <div class="lms-field lms-field-wide">
                                            <label for="announcement-{{ $announcement->id }}-message">Message</label>
                                            <textarea id="announcement-{{ $announcement->id }}-message" name="message" rows="4" maxlength="3000" required>{{ $announcement->message }}</textarea>
                                        </div>
                                        <input type="hidden" name="audience" value="{{ $announcement->audience ?: 'trainees' }}">
                                        <div class="lms-form-options lms-field-wide">
                                            <label class="lms-check"><input type="hidden" name="is_pinned" value="0"><input type="checkbox" name="is_pinned" value="1" @checked($announcement->is_pinned)><span>Pin to top</span></label>
                                            <label class="lms-check"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" @checked($announcement->is_published)><span>Published</span></label>
                                            <div class="lms-field lms-compact-field">
                                                <label for="announcement-{{ $announcement->id }}-posted">Post at</label>
                                                <input id="announcement-{{ $announcement->id }}-posted" type="datetime-local" name="posted_at" value="{{ $announcement->posted_at?->format('Y-m-d\TH:i') }}">
                                            </div>
                                            <div class="lms-field lms-compact-field">
                                                <label for="announcement-{{ $announcement->id }}-expires">Hide after</label>
                                                <input id="announcement-{{ $announcement->id }}-expires" type="datetime-local" name="expires_at" value="{{ $announcement->expires_at?->format('Y-m-d\TH:i') }}">
                                            </div>
                                        </div>
                                        <div class="lms-form-actions lms-field-wide">
                                            <button type="button" class="secondary-action" data-close-inline-editor>Cancel</button>
                                            <button class="primary-action">Save changes</button>
                                        </div>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('trainer.announcements.destroy', $announcement) }}" data-confirm="Delete this announcement? Trainees will no longer see it.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="lms-text-action is-danger">Delete</button>
                                </form>
                            </div>
                        </footer>
                    </article>
                @empty
                    <div class="lms-empty-state">
                        <x-dashboard-icon name="bell" />
                        <h2>No class posts yet</h2>
                        <p>Use the announcement composer to publish the first update for {{ $selectedBatch?->name ?? 'your classes' }}.</p>
                    </div>
                @endforelse
            </section>

            @if(method_exists($announcements, 'hasPages') && $announcements->hasPages())
                <div class="lms-pagination">{{ $announcements->links() }}</div>
            @endif
        </main>

        <aside class="lms-stream-sidebar" aria-label="Stream overview">
            <section class="lms-side-card">
                <p class="lms-eyebrow">At a glance</p>
                <dl class="lms-stat-list">
                    <div><dt>Classes</dt><dd>{{ $batches->count() }}</dd></div>
                    <div><dt>All posts</dt><dd>{{ $stats['total'] }}</dd></div>
                    <div><dt>Published</dt><dd>{{ $stats['published'] }}</dd></div>
                    <div><dt>Scheduled</dt><dd>{{ $stats['scheduled'] }}</dd></div>
                </dl>
            </section>
            <section class="lms-side-card">
                <h2>Posting guide</h2>
                <ul class="lms-guidance-list">
                    <li>Use announcements for lasting class updates.</li>
                    <li>Use reminders for deadlines and room changes.</li>
                    <li>Pin only the item trainees need first.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>
@endsection
