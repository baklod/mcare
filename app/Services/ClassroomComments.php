<?php

namespace App\Services;

use App\Models\ClassroomComment;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ClassroomComments
{
    public function resolve(string $type, int $id): TrainingModule|Quiz
    {
        return match ($type) {
            'module' => TrainingModule::query()->findOrFail($id),
            'quiz' => Quiz::query()->findOrFail($id),
            default => abort(404),
        };
    }

    public function authorizeView(User $user, TrainingModule|Quiz $commentable): void
    {
        Gate::forUser($user)->authorize('view', $commentable);
    }

    /** @return Collection<int, ClassroomComment> */
    public function visibleFor(User $user, TrainingModule|Quiz $commentable): Collection
    {
        $query = ClassroomComment::query()
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->with(['author', 'recipient'])
            ->oldest('created_at')
            ->limit(100);

        if ($user->role === 'trainee') {
            $query->where(function ($visibility) use ($user): void {
                $visibility->where('visibility', ClassroomComment::VISIBILITY_CLASS)
                    ->orWhere(function ($private) use ($user): void {
                        $private->where('visibility', ClassroomComment::VISIBILITY_PRIVATE)
                            ->where(function ($participant) use ($user): void {
                                $participant->where('author_id', $user->id)
                                    ->orWhere('recipient_user_id', $user->id);
                            });
                    });
            });
        }

        return $query->get();
    }

    /** @return Collection<int, User> */
    public function privateRecipients(User $user, TrainingModule|Quiz $commentable): Collection
    {
        if ($user->role === 'trainee') {
            return collect([$this->trainerFor($commentable)])->filter()->values();
        }

        $recipients = $this->targetTrainees($commentable);

        if ($user->role === 'admin') {
            $recipients->push($this->trainerFor($commentable));
        }

        return $recipients
            ->filter(fn (?User $recipient): bool => $recipient !== null && $recipient->id !== $user->id)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, User> */
    public function notificationRecipients(
        User $author,
        ClassroomComment $comment,
        TrainingModule|Quiz $commentable,
    ): Collection {
        if ($comment->isPrivate()) {
            return User::query()
                ->whereKey($comment->recipient_user_id)
                ->get()
                ->reject(fn (User $recipient): bool => $recipient->id === $author->id)
                ->values();
        }

        $recipients = $author->role === 'trainee'
            ? collect([$this->trainerFor($commentable)])
            : $this->targetTrainees($commentable);

        if ($author->role === 'admin') {
            $recipients->push($this->trainerFor($commentable));
        }

        return $recipients
            ->filter(fn (?User $recipient): bool => $recipient !== null && $recipient->id !== $author->id)
            ->unique('id')
            ->values();
    }

    public function typeFor(TrainingModule|Quiz $commentable): string
    {
        return $commentable instanceof TrainingModule ? 'module' : 'quiz';
    }

    public function labelFor(TrainingModule|Quiz $commentable): string
    {
        return $commentable->title;
    }

    public function pathFor(User $user, TrainingModule|Quiz $commentable): string
    {
        $anchor = '#classroom-comments';

        if ($commentable instanceof TrainingModule) {
            return match ($user->role) {
                'trainer' => route('trainer.modules.show', $commentable).$anchor,
                'trainee' => route('trainee.modules.show', $commentable).$anchor,
                default => route('classroom-comments.index', ['type' => 'module', 'id' => $commentable->id]).$anchor,
            };
        }

        return match ($user->role) {
            'trainer' => route('trainer.quizzes.edit', $commentable).$anchor,
            'trainee' => route('trainee.quizzes.show', $commentable).$anchor,
            default => route('classroom-comments.index', ['type' => 'quiz', 'id' => $commentable->id]).$anchor,
        };
    }

    public function backPathFor(User $user, TrainingModule|Quiz $commentable): string
    {
        if ($user->role === 'admin') {
            return route('admin.learning.modules');
        }

        return $this->pathFor($user, $commentable);
    }

    public function batchIdFor(TrainingModule|Quiz $commentable): ?int
    {
        return $commentable->training_batch_id ? (int) $commentable->training_batch_id : null;
    }

    private function trainerFor(TrainingModule|Quiz $commentable): ?User
    {
        return $commentable->relationLoaded('trainer')
            ? $commentable->trainer
            : $commentable->trainer()->first();
    }

    /** @return Collection<int, User> */
    private function targetTrainees(TrainingModule|Quiz $commentable): Collection
    {
        $query = EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->whereNotNull('user_id')
            ->with('user');

        if ($commentable->target_enrollment_application_id !== null) {
            $query->whereKey($commentable->target_enrollment_application_id);
        } elseif ($commentable->training_batch_id !== null) {
            $query->where('training_batch_id', $commentable->training_batch_id);
        }

        return $query->get()
            ->pluck('user')
            ->filter(fn (?User $user): bool => $user !== null && $user->role === 'trainee')
            ->unique('id')
            ->values();
    }
}
