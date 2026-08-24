<?php

namespace App\Policies;

use App\Models\ClassroomComment;
use App\Models\Quiz;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ClassroomCommentPolicy
{
    public function view(User $user, ClassroomComment $comment): bool
    {
        if (! $comment->commentable
            || ! Gate::forUser($user)->allows('view', $comment->commentable)) {
            return false;
        }

        if (! $comment->isPrivate() || $user->role === 'admin') {
            return true;
        }

        if ($this->trainerOwns($user, $comment->commentable)) {
            return true;
        }

        return $comment->author_id === $user->id
            || $comment->recipient_user_id === $user->id;
    }

    public function update(User $user, ClassroomComment $comment): bool
    {
        return $comment->author_id === $user->id
            && $user->hasPermissionTo('comments.create')
            && $this->view($user, $comment);
    }

    public function delete(User $user, ClassroomComment $comment): bool
    {
        if ($comment->author_id === $user->id && $this->view($user, $comment)) {
            return true;
        }

        if ($user->role === 'admin' && $user->hasPermissionTo('comments.moderate')) {
            return true;
        }

        return $user->hasPermissionTo('comments.moderate')
            && $comment->commentable
            && $this->trainerOwns($user, $comment->commentable);
    }

    private function trainerOwns(User $user, TrainingModule|Quiz $commentable): bool
    {
        return $user->role === 'trainer'
            && (int) $commentable->trainer_id === $user->id;
    }
}
