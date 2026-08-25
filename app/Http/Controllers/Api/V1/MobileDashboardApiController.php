<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardApiController extends Controller
{
    /**
     * Return structured JSON summary for mobile app dashboard viewports.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'unauthenticated',
                'message' => 'Valid authentication token or session required.',
            ], 401);
        }

        $application = EnrollmentApplication::with('batch')
            ->where('user_id', $user->id)
            ->first();

        $batchId = $application?->training_batch_id;

        $announcements = TrainerAnnouncement::with('trainer:id,name')
            ->when($batchId, fn ($query) => $query->where('training_batch_id', $batchId))
            ->where('is_published', true)
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $modules = $application && $application->status === EnrollmentApplication::STATUS_APPROVED
            ? TrainingModule::query()
                ->availableTo($application)
                ->orderBy('position')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get()
            : collect();

        $quizzes = $application && $application->status === EnrollmentApplication::STATUS_APPROVED
            ? Quiz::query()
                ->withCount('questions')
                ->released()
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn (Quiz $quiz): bool => $quiz->targets($application))
                ->take(5)
                ->values()
            : collect();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar_url' => $user->avatar_url,
                ],
                'application' => $application ? [
                    'id' => $application->id,
                    'reference_number' => $application->reference_number,
                    'status' => $application->status,
                    'batch' => $application->batch ? [
                        'name' => $application->batch->name,
                        'year' => $application->batch->year,
                        'schedule' => $application->batch->schedule,
                    ] : null,
                ] : null,
                'announcements' => $announcements,
                'quizzes' => $quizzes,
                'modules' => $modules,
            ],
        ]);
    }
}
