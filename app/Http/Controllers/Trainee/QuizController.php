<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\AttendanceService;
use App\Services\ClassroomComments;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return $this->approvalRedirect();
        }

        return redirect()->route('trainee.modules.index');
    }

    public function show(
        Request $request,
        Quiz $quiz,
        ClassroomComments $comments,
    ): View|RedirectResponse {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return $this->approvalRedirect();
        }

        abort_unless($quiz->isReleasedAt() && $quiz->targets($application), 404);
        $quiz->load(['trainer', 'batch', 'questions', 'attendances']);
        $attempts = $quiz->attempts()
            ->where('enrollment_application_id', $application->id)
            ->latest('attempt_number')
            ->get();

        $attendance = $quiz->attendanceFor($application);
        $canTimeIn = $quiz->requires_time_in && $quiz->isTimeInAllowed() && ! $attendance;

        return view('trainee.quizzes.show', [
            'application' => $application,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'canStart' => $quiz->isOpenAt() && $quiz->attemptsRemainingFor($application) > 0,
            'attendance' => $attendance,
            'canTimeIn' => $canTimeIn,
            'classroomComments' => $comments->visibleFor($request->user(), $quiz),
            'privateCommentRecipients' => $comments->privateRecipients($request->user(), $quiz),
        ]);
    }

    public function timeIn(
        Request $request,
        Quiz $quiz,
        AttendanceService $attendanceService,
    ): RedirectResponse {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return $this->approvalRedirect();
        }

        abort_unless($quiz->isReleasedAt() && $quiz->targets($application), 404);

        if (! $quiz->requires_time_in) {
            return redirect()
                ->route('trainee.quizzes.show', $quiz)
                ->with('error', 'This activity does not require a separate attendance time-in.');
        }

        if (! $quiz->isTimeInAllowed()) {
            return redirect()
                ->route('trainee.quizzes.show', $quiz)
                ->with('error', 'The time-in window for this activity is closed or has expired.');
        }

        $attendance = $attendanceService->recordActivityTimeIn($quiz, $application, $request);

        AdminActivityLog::record($request->user(), 'trainee.activity.timed-in', $attendance, [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'timed_in_at' => $attendance->timed_in_at?->toIso8601String(),
        ]);

        return redirect()
            ->route('trainee.quizzes.show', $quiz)
            ->with('status', 'Your time-in for this activity has been successfully recorded as Present.');
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return $this->approvalRedirect();
        }

        abort_unless($quiz->isReleasedAt() && $quiz->targets($application), 404);

        if (! $quiz->isOpenAt()) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz is closed or is not available yet.',
            ]);
        }

        $attempt = DB::transaction(function () use ($quiz, $application): QuizAttempt {
            $lockedQuiz = Quiz::query()->lockForUpdate()->findOrFail($quiz->id);

            // Recheck the locked row so publishing or audience changes that
            // raced with this request cannot create an unauthorized attempt.
            abort_unless(
                $lockedQuiz->isReleasedAt() && $lockedQuiz->targets($application),
                404
            );

            if (! $lockedQuiz->isOpenAt()) {
                throw ValidationException::withMessages([
                    'quiz' => 'This quiz is closed or is not available yet.',
                ]);
            }

            $existingAttempt = $lockedQuiz->attempts()
                ->where('enrollment_application_id', $application->id)
                ->where('status', QuizAttempt::STATUS_IN_PROGRESS)
                ->latest('attempt_number')
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            $usedAttempts = $lockedQuiz->attempts()
                ->where('enrollment_application_id', $application->id)
                ->count();

            if ($usedAttempts >= $lockedQuiz->attempt_limit) {
                throw ValidationException::withMessages([
                    'quiz' => 'You have used all allowed attempts for this quiz.',
                ]);
            }

            return QuizAttempt::create([
                'quiz_id' => $lockedQuiz->id,
                'enrollment_application_id' => $application->id,
                'attempt_number' => $usedAttempts + 1,
                'status' => QuizAttempt::STATUS_IN_PROGRESS,
                'answers' => [],
                'started_at' => now(),
            ]);
        });

        AdminActivityLog::record($request->user(), 'trainee.quiz.started', $attempt, [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'attempt_number' => $attempt->attempt_number,
        ]);

        return redirect()->route('trainee.quiz-attempts.show', $attempt);
    }

    private function availableQuizQuery(EnrollmentApplication $application)
    {
        return Quiz::query()
            ->released()
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(function ($scopeQuery) use ($application) {
                                $scopeQuery->whereNull('training_batch_id')
                                    ->orWhere('training_batch_id', $application->training_batch_id);
                            });
                    });
            });
    }

    private function approvedApplicationFor(Request $request): ?EnrollmentApplication
    {
        return EnrollmentApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();
    }

    private function approvalRedirect(): RedirectResponse
    {
        return redirect()
            ->route('payment.show')
            ->with('payment_notice', 'Your trainee classroom opens after admin approval.');
    }
}
