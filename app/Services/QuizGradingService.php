<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;

class QuizGradingService
{
    /**
     * Persist an automatically graded attempt using only server-owned answer keys.
     *
     * @param  array<int|string, mixed>  $answers
     */
    public function grade(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        return DB::transaction(function () use ($attempt, $answers): QuizAttempt {
            $lockedAttempt = QuizAttempt::query()
                ->lockForUpdate()
                ->findOrFail($attempt->getKey());

            // Retried submissions are idempotent and never overwrite a final score.
            if ($lockedAttempt->isGraded()) {
                return $lockedAttempt;
            }

            $quiz = Quiz::query()
                ->with('questions')
                ->findOrFail($lockedAttempt->quiz_id);
            $result = $this->calculate($quiz, $answers);
            $gradedAt = now();

            $lockedAttempt->forceFill([
                'status' => QuizAttempt::STATUS_GRADED,
                'answers' => $result['answers'],
                'earned_points' => $result['earned_points'],
                'total_points' => $result['total_points'],
                'score_percent' => $result['score_percent'],
                'passed' => $result['passed'],
                'started_at' => $lockedAttempt->started_at ?: $gradedAt,
                'submitted_at' => $gradedAt,
                'graded_at' => $gradedAt,
            ])->save();

            return $lockedAttempt->refresh();
        });
    }

    /**
     * Calculate a score without trusting client-provided points or answer keys.
     *
     * @param  array<int|string, mixed>  $answers
     * @return array{
     *     answers: array<int|string, int|null>,
     *     earned_points: float,
     *     total_points: float,
     *     score_percent: float,
     *     passed: bool
     * }
     */
    public function calculate(Quiz $quiz, array $answers): array
    {
        $quiz->loadMissing('questions');

        $normalizedAnswers = [];
        $earnedPoints = 0.0;
        $totalPoints = 0.0;

        $quiz->questions->each(function (QuizQuestion $question) use (
            $answers,
            &$normalizedAnswers,
            &$earnedPoints,
            &$totalPoints,
        ): void {
            $questionId = $question->getKey();
            $answer = $answers[$questionId] ?? $answers[(string) $questionId] ?? null;
            $normalizedAnswer = $question->normalizedOptionIndex($answer);
            $points = (float) $question->points;

            // Extra client keys are discarded; only this quiz's questions are persisted.
            $normalizedAnswers[(string) $questionId] = $normalizedAnswer;
            $totalPoints += $points;

            if ($question->isCorrectOption($normalizedAnswer)) {
                $earnedPoints += $points;
            }
        });

        $earnedPoints = round($earnedPoints, 2);
        $totalPoints = round($totalPoints, 2);
        $scorePercent = $totalPoints > 0
            ? round(($earnedPoints / $totalPoints) * 100, 2)
            : 0.0;

        return [
            'answers' => $normalizedAnswers,
            'earned_points' => $earnedPoints,
            'total_points' => $totalPoints,
            'score_percent' => $scorePercent,
            'passed' => $scorePercent >= (float) $quiz->passing_score_percent,
        ];
    }
}
