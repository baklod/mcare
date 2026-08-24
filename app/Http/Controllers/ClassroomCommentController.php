<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\ClassroomComment;
use App\Notifications\ClassroomCommentPosted;
use App\Services\ClassroomComments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassroomCommentController extends Controller
{
    public function index(
        Request $request,
        string $type,
        int $id,
        ClassroomComments $comments,
    ): View {
        $commentable = $comments->resolve($type, $id);
        $comments->authorizeView($request->user(), $commentable);

        return view('classroom.comments.index', [
            'commentable' => $commentable,
            'classroomComments' => $comments->visibleFor($request->user(), $commentable),
            'privateCommentRecipients' => $comments->privateRecipients($request->user(), $commentable),
            'returnUrl' => $comments->backPathFor($request->user(), $commentable),
        ]);
    }

    public function store(Request $request, ClassroomComments $comments): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('comments.create'), 403);

        $validated = $request->validate([
            'commentable_type' => ['required', Rule::in(['module', 'quiz'])],
            'commentable_id' => ['required', 'integer', 'min:1'],
            'visibility' => ['required', Rule::in([
                ClassroomComment::VISIBILITY_CLASS,
                ClassroomComment::VISIBILITY_PRIVATE,
            ])],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'body' => ['required', 'string', 'min:1', 'max:2000', 'not_regex:/<[^>]+>/u'],
        ], [
            'body.not_regex' => 'Comments must be plain text and cannot contain HTML.',
        ]);

        $commentable = $comments->resolve(
            $validated['commentable_type'],
            (int) $validated['commentable_id'],
        );
        $comments->authorizeView($request->user(), $commentable);

        $recipient = null;
        if ($validated['visibility'] === ClassroomComment::VISIBILITY_PRIVATE) {
            $eligible = $comments->privateRecipients($request->user(), $commentable);
            $recipient = $request->user()->role === 'trainee'
                ? $eligible->first()
                : $eligible->firstWhere('id', (int) ($validated['recipient_user_id'] ?? 0));

            if (! $recipient) {
                throw ValidationException::withMessages([
                    'recipient_user_id' => 'Choose an eligible classroom participant for this private comment.',
                ]);
            }
        }

        $comment = $commentable->comments()->create([
            'author_id' => $request->user()->id,
            'recipient_user_id' => $recipient?->id,
            'training_batch_id' => $comments->batchIdFor($commentable),
            'visibility' => $validated['visibility'],
            'body' => trim($validated['body']),
        ]);
        $comment->load(['author', 'recipient', 'commentable']);

        AdminActivityLog::record($request->user(), 'classroom.comment.created', $comment, [
            'commentable_type' => $validated['commentable_type'],
            'commentable_id' => $commentable->getKey(),
            'visibility' => $comment->visibility,
            'recipient_user_id' => $comment->recipient_user_id,
        ]);

        $recipients = $comments->notificationRecipients($request->user(), $comment, $commentable);
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ClassroomCommentPosted($comment));
        }

        return redirect()
            ->to($comments->pathFor($request->user(), $commentable))
            ->with('saved', $comment->isPrivate() ? 'Private comment sent.' : 'Class comment posted.');
    }

    public function update(
        Request $request,
        ClassroomComment $comment,
        ClassroomComments $comments,
    ): RedirectResponse {
        $this->authorize('update', $comment);
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000', 'not_regex:/<[^>]+>/u'],
        ], [
            'body.not_regex' => 'Comments must be plain text and cannot contain HTML.',
        ]);

        $commentable = $comment->commentable;
        abort_unless($commentable, 404);
        $comment->update([
            'body' => trim($validated['body']),
            'edited_at' => now(),
        ]);

        AdminActivityLog::record($request->user(), 'classroom.comment.updated', $comment, [
            'visibility' => $comment->visibility,
        ]);

        return redirect()
            ->to($comments->pathFor($request->user(), $commentable))
            ->with('saved', 'Comment updated.');
    }

    public function destroy(
        Request $request,
        ClassroomComment $comment,
        ClassroomComments $comments,
    ): RedirectResponse {
        $this->authorize('delete', $comment);
        $commentable = $comment->commentable;
        abort_unless($commentable, 404);

        AdminActivityLog::record($request->user(), 'classroom.comment.deleted', $comment, [
            'visibility' => $comment->visibility,
        ]);
        $comment->delete();

        return redirect()
            ->to($comments->pathFor($request->user(), $commentable))
            ->with('saved', 'Comment removed.');
    }
}
