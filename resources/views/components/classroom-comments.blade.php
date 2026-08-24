@props([
    'commentable',
    'comments' => collect(),
    'privateRecipients' => collect(),
])

@php
    $viewer = auth()->user();
    $commentableType = $commentable instanceof \App\Models\TrainingModule ? 'module' : 'quiz';
    $classComments = collect($comments)->where('visibility', \App\Models\ClassroomComment::VISIBILITY_CLASS);
    $privateComments = collect($comments)->where('visibility', \App\Models\ClassroomComment::VISIBILITY_PRIVATE);
    $privateRecipientList = collect($privateRecipients);
    $traineePrivateRecipient = $viewer?->role === 'trainee' ? $privateRecipientList->first() : null;
@endphp

<section id="classroom-comments" class="lms-comments-panel" aria-labelledby="classroom-comments-title">
    <header class="lms-comments-header">
        <div>
            <p class="lms-eyebrow">Classroom conversation</p>
            <h2 id="classroom-comments-title">Comments and private feedback</h2>
            <p>Class comments are visible to participants. Private comments are limited to their participants and authorized classroom staff.</p>
        </div>
        <span class="lms-comment-count">{{ collect($comments)->count() }} {{ str('comment')->plural(collect($comments)->count()) }}</span>
    </header>

    <div class="lms-comments-grid">
        <section class="lms-comment-column" aria-labelledby="class-comments-title">
            <div class="lms-comment-column-heading">
                <div><h3 id="class-comments-title">Class comments</h3><p>Visible to everyone who can open this classwork item.</p></div>
            </div>

            <div class="lms-comment-feed" aria-live="polite">
                @forelse($classComments as $comment)
                    <article class="lms-comment" id="comment-{{ $comment->id }}">
                        <div class="lms-comment-avatar" aria-hidden="true">{{ strtoupper(substr($comment->author?->name ?? 'M', 0, 1)) }}</div>
                        <div class="lms-comment-content">
                            <div class="lms-comment-meta">
                                <strong>{{ $comment->author?->name ?? 'MCARE user' }}</strong>
                                <span>{{ $comment->created_at?->format('M d, Y g:i A') }}@if($comment->edited_at) · Edited @endif</span>
                            </div>
                            <p>{!! nl2br(e($comment->body)) !!}</p>
                            @canany(['update', 'delete'], $comment)
                                <details class="lms-comment-manage">
                                    <summary>Manage</summary>
                                    <div>
                                        @can('update', $comment)
                                            <form method="POST" action="{{ route('classroom-comments.update', $comment) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea name="body" maxlength="2000" required class="form-field min-h-20">{{ $comment->body }}</textarea>
                                                <button class="secondary-action text-xs">Save edit</button>
                                            </form>
                                        @endcan
                                        @can('delete', $comment)
                                            <form method="POST" action="{{ route('classroom-comments.destroy', $comment) }}" data-confirm="Remove this class comment?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="lms-text-action is-danger text-xs">Remove</button>
                                            </form>
                                        @endcan
                                    </div>
                                </details>
                            @endcanany
                        </div>
                    </article>
                @empty
                    <p class="lms-comment-empty">No class comments yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('classroom-comments.store') }}" class="lms-comment-composer" data-dashboard-dialog-form data-submit-label="Posting...">
                @csrf
                <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
                <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
                <input type="hidden" name="visibility" value="class">
                <label for="class-comment-{{ $commentableType }}-{{ $commentable->id }}">Add a class comment</label>
                <textarea id="class-comment-{{ $commentableType }}-{{ $commentable->id }}" name="body" required maxlength="2000" rows="3" placeholder="Write a helpful question, answer, or update..."></textarea>
                <div><span>Plain text · 2,000 characters maximum</span><button class="primary-action text-xs" data-action-button>Post comment</button></div>
            </form>
        </section>

        <section class="lms-comment-column is-private" aria-labelledby="private-comments-title">
            <div class="lms-comment-column-heading">
                <div><h3 id="private-comments-title">Private comments</h3><p>Not visible to other trainees.</p></div>
            </div>

            <div class="lms-comment-feed" aria-live="polite">
                @forelse($privateComments as $comment)
                    @php
                        $privateCounterpart = $comment->author_id === $viewer?->id
                            ? $comment->recipient
                            : ($comment->recipient_user_id === $viewer?->id ? $comment->author : $comment->recipient);
                    @endphp
                    <article class="lms-comment is-private" id="comment-{{ $comment->id }}">
                        <div class="lms-comment-avatar" aria-hidden="true">{{ strtoupper(substr($comment->author?->name ?? 'M', 0, 1)) }}</div>
                        <div class="lms-comment-content">
                            <div class="lms-comment-meta">
                                <strong>{{ $comment->author?->name ?? 'MCARE user' }}</strong>
                                <span>{{ $comment->created_at?->format('M d, Y g:i A') }}@if($comment->edited_at) · Edited @endif</span>
                            </div>
                            <span class="lms-private-context">Private with {{ $privateCounterpart?->name ?? 'classroom staff' }}</span>
                            <p>{!! nl2br(e($comment->body)) !!}</p>
                            @canany(['update', 'delete'], $comment)
                                <details class="lms-comment-manage">
                                    <summary>Manage</summary>
                                    <div>
                                        @can('update', $comment)
                                            <form method="POST" action="{{ route('classroom-comments.update', $comment) }}" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea name="body" maxlength="2000" required class="form-field min-h-20">{{ $comment->body }}</textarea>
                                                <button class="secondary-action text-xs">Save edit</button>
                                            </form>
                                        @endcan
                                        @can('delete', $comment)
                                            <form method="POST" action="{{ route('classroom-comments.destroy', $comment) }}" data-confirm="Remove this private comment?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="lms-text-action is-danger text-xs">Remove</button>
                                            </form>
                                        @endcan
                                    </div>
                                </details>
                            @endcanany
                        </div>
                    </article>
                @empty
                    <p class="lms-comment-empty">No private comments in this conversation yet.</p>
                @endforelse
            </div>

            @if($privateRecipientList->isNotEmpty())
                <form method="POST" action="{{ route('classroom-comments.store') }}" class="lms-comment-composer is-private" data-dashboard-dialog-form data-submit-label="Sending...">
                    @csrf
                    <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
                    <input type="hidden" name="commentable_id" value="{{ $commentable->id }}">
                    <input type="hidden" name="visibility" value="private">
                    @if($traineePrivateRecipient)
                        <input type="hidden" name="recipient_user_id" value="{{ $traineePrivateRecipient->id }}">
                        <p class="lms-private-recipient">Private conversation with <strong>{{ $traineePrivateRecipient->name }}</strong></p>
                    @else
                        <label for="private-recipient-{{ $commentableType }}-{{ $commentable->id }}">Private recipient</label>
                        <select id="private-recipient-{{ $commentableType }}-{{ $commentable->id }}" name="recipient_user_id" class="form-field" required>
                            <option value="">Choose trainee or trainer</option>
                            @foreach($privateRecipientList as $recipient)
                                <option value="{{ $recipient->id }}">{{ $recipient->name }} · {{ ucfirst($recipient->role) }}</option>
                            @endforeach
                        </select>
                    @endif
                    <label for="private-comment-{{ $commentableType }}-{{ $commentable->id }}">Add a private comment</label>
                    <textarea id="private-comment-{{ $commentableType }}-{{ $commentable->id }}" name="body" required maxlength="2000" rows="3" placeholder="Write feedback that only the selected participant and classroom staff can read..."></textarea>
                    <div><span>Private · Plain text</span><button class="primary-action text-xs" data-action-button>Send privately</button></div>
                </form>
            @else
                <p class="lms-comment-empty">No eligible private recipient is assigned to this classwork item.</p>
            @endif
        </section>
    </div>
</section>
