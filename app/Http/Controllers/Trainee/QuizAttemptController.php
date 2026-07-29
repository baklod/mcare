<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\QuizAttempt;
use App\Services\QuizGradingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuizAttemptController extends Controller
{
    public function show(
        Request $request,
        QuizAttempt $attempt,
        QuizGradingService $gradingService,
    ): View|RedirectResponse {
        if ($attempt->isGraded()) {
            $this->authorize('view', $attempt);

            return redirect()->route('trainee.quiz-attempts.result', $attempt);
        }

        $this->authorize('update', $attempt);
        $attempt->load(['quiz.questions', 'application']);
        $this->ensureAttemptIsAvailable($attempt);

        if ($attempt->isExpiredAt()) {
            $gradedAttempt = $gradingService->grade($attempt, $attempt->answers ?? []);
            $this->recordSubmission($request, $gradedAttempt, true);

            return redirect()
                ->route('trainee.quiz-attempts.result', $gradedAttempt)
                ->with('saved', 'The quiz time ended and this attempt was finalized.');
        }

        return view('trainee.quizzes.take', [
            'application' => $attempt->application,
            'quiz' => $attempt->quiz,
            'attempt' => $attempt,
            'remainingSeconds' => $attempt->remainingSeconds(),
        ]);
    }

    public function submit(
        Request $request,
        QuizAttempt $attempt,
        QuizGradingService $gradingService,
    ): RedirectResponse {
        if ($attempt->isGraded()) {
            $this->authorize('view', $attempt);

            return redirect()->route('trainee.quiz-attempts.result', $attempt);
        }

        $this->authorize('submit', $attempt);
        $attempt->load(['quiz.questions', 'application']);
        $this->ensureAttemptIsAvailable($attempt);
        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'integer', 'min:0', 'max:5'],
        ]);
        $expired = $attempt->isExpiredAt();
        $answers = $validated['answers'] ?? [];

        // A short grace window accepts the browser's automatic submission at
        // zero. Later requests cannot use answers chosen after the deadline.
        if ($expired && ! $attempt->acceptsExpirationSubmissionAt()) {
            $answers = $attempt->answers ?? [];
        }

        $gradedAttempt = $gradingService->grade($attempt, $answers);
        $this->recordSubmission($request, $gradedAttempt, $expired);

        $response = redirect()->route('trainee.quiz-attempts.result', $gradedAttempt);

        if ($expired) {
            $response->with('saved', 'The quiz time ended and this attempt was finalized.');
        }

        return $response;
    }

    public function result(Request $request, QuizAttempt $attempt): View|RedirectResponse
    {
        $this->authorize('view', $attempt);

        if (! $attempt->isGraded()) {
            return redirect()->route('trainee.quiz-attempts.show', $attempt);
        }

        $attempt->load(['quiz.trainer', 'quiz.batch', 'quiz.questions', 'application']);
        $quiz = $attempt->quiz;
        $gradedAttemptCount = $quiz->attempts()
            ->where('enrollment_application_id', $attempt->enrollment_application_id)
            ->where('status', QuizAttempt::STATUS_GRADED)
            ->count();
        $answerReviewAvailable = (
            $quiz->due_at !== null
            && $quiz->due_at->lte(now())
        ) || $gradedAttemptCount >= $quiz->attempt_limit;

        return view('trainee.quizzes.result', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'answerReviewAvailable' => $answerReviewAvailable,
        ]);
    }

    private function ensureAttemptIsAvailable(QuizAttempt $attempt): void
    {
        abort_unless(
            $attempt->application->status === EnrollmentApplication::STATUS_APPROVED
                && $attempt->quiz->isReleasedAt()
                && $attempt->quiz->targets($attempt->application),
            404
        );
    }

    private function recordSubmission(
        Request $request,
        QuizAttempt $attempt,
        bool $expired,
    ): void {
        $attempt->loadMissing('quiz');

        AdminActivityLog::record(
            $request->user(),
            $expired ? 'trainee.quiz.expired-finalized' : 'trainee.quiz.submitted',
            $attempt,
            [
                'quiz_id' => $attempt->quiz_id,
                'quiz_title' => $attempt->quiz->title,
                'attempt_number' => $attempt->attempt_number,
                'score_percent' => $attempt->score_percent,
                'passed' => $attempt->passed,
                'expired' => $expired,
            ]
        );
    }
}
