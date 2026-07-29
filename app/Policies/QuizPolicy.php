<?php

namespace App\Policies;

use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') && $user->hasPermissionTo('quizzes.manage')
            ? true
            : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('quizzes.manage')
            || $user->hasPermissionTo('quizzes.take')
            || $user->hasPermissionTo('grades.view');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if ($this->trainerOwns($user, $quiz)) {
            return true;
        }

        $application = $this->approvedApplicationFor($user);

        return $application !== null
            && $user->hasPermissionTo('quizzes.take')
            && $quiz->isReleasedAt()
            && $quiz->targets($application);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('trainer') && $user->hasPermissionTo('quizzes.manage');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $this->trainerOwns($user, $quiz);
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->trainerOwns($user, $quiz);
    }

    public function publish(User $user, Quiz $quiz): bool
    {
        return $this->trainerOwns($user, $quiz);
    }

    public function take(
        User $user,
        Quiz $quiz,
        ?EnrollmentApplication $application = null,
    ): bool {
        $application ??= $this->approvedApplicationFor($user);

        return $application !== null
            && (int) $application->user_id === $user->getKey()
            && $user->hasPermissionTo('quizzes.take')
            && $quiz->isOpenAt()
            && $quiz->targets($application)
            && $quiz->attemptsRemainingFor($application) > 0;
    }

    public function viewGrades(User $user, Quiz $quiz): bool
    {
        return $user->hasPermissionTo('grades.view')
            && $this->trainerOwns($user, $quiz);
    }

    public function viewResults(User $user, Quiz $quiz): bool
    {
        return $this->viewGrades($user, $quiz);
    }

    private function trainerOwns(User $user, Quiz $quiz): bool
    {
        return $user->hasRole('trainer')
            && $user->hasPermissionTo('quizzes.manage')
            && (int) $quiz->trainer_id === $user->getKey();
    }

    private function approvedApplicationFor(User $user): ?EnrollmentApplication
    {
        return EnrollmentApplication::query()
            ->where('user_id', $user->getKey())
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();
    }
}
