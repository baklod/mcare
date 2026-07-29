<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($user->hasRole('admin') && $user->hasPermissionTo('grades.view')) {
            return true;
        }

        if ($user->hasRole('trainer')
            && $user->hasPermissionTo('grades.view')
            && (int) $attempt->quiz()->value('trainer_id') === $user->getKey()) {
            return true;
        }

        return $user->hasPermissionTo('grades.view')
            && (int) $attempt->application()->value('user_id') === $user->getKey();
    }

    public function update(User $user, QuizAttempt $attempt): bool
    {
        return $this->traineeOwnsOpenAttempt($user, $attempt);
    }

    public function submit(User $user, QuizAttempt $attempt): bool
    {
        return $this->traineeOwnsOpenAttempt($user, $attempt);
    }

    public function grade(User $user, QuizAttempt $attempt): bool
    {
        return $user->hasPermissionTo('grades.view')
            && (
                $user->hasRole('admin')
                || (
                    $user->hasRole('trainer')
                    && (int) $attempt->quiz()->value('trainer_id') === $user->getKey()
                )
            );
    }

    private function traineeOwnsOpenAttempt(User $user, QuizAttempt $attempt): bool
    {
        return $user->hasRole('trainee')
            && $user->hasPermissionTo('quizzes.take')
            && ! $attempt->isGraded()
            && (int) $attempt->application()->value('user_id') === $user->getKey();
    }
}
