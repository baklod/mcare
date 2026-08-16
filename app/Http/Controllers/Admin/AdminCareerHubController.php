<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\CareerOpportunityPublished;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCareerHubController extends Controller
{
    public function index(): View
    {
        return view('admin.learning.alumni-jobs', [
            'approvedTrainees' => EnrollmentApplication::query()
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->count(),
            'completedBatches' => TrainingBatch::query()
                ->whereNotNull('training_ends_at')
                ->where('training_ends_at', '<=', now())
                ->count(),
            'alumniAccounts' => User::query()->where('role', 'alumni')->count(),
            'publishedJobs' => CareerOpportunity::query()->where('is_published', true)->count(),
            'jobs' => CareerOpportunity::query()
                ->with('creator')
                ->latest('created_at')
                ->paginate(12),
            'employmentTypes' => CareerOpportunity::employmentTypes(),
        ]);
    }

    public function preview(Request $request): View
    {
        AdminActivityLog::record($request->user(), 'career.portal.previewed');

        return view('alumni.dashboard', [
            // Administrators see the exact published feed without impersonating an alumni account.
            'isAdminPreview' => true,
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request, 'careerCreate');
        $shouldPublish = $request->boolean('is_published');

        $opportunity = CareerOpportunity::create([
            ...$validated,
            'created_by_id' => $request->user()->id,
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish ? now() : null,
        ]);

        AdminActivityLog::record($request->user(), 'career.opportunity.created', $opportunity, [
            'title' => $opportunity->title,
            'employer' => $opportunity->employer,
            'published' => $opportunity->is_published,
        ]);

        if ($shouldPublish) {
            $this->notifyAlumni($opportunity);
        }

        return back()->with('saved', $shouldPublish
            ? 'Career opportunity published and alumni were notified.'
            : 'Career opportunity saved as a draft.');
    }

    public function update(Request $request, CareerOpportunity $careerOpportunity): RedirectResponse
    {
        $validated = $this->validatedPayload($request);
        $wasPublished = $careerOpportunity->is_published;
        $shouldPublish = $request->boolean('is_published');

        $careerOpportunity->update([
            ...$validated,
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish
                ? ($careerOpportunity->published_at ?: now())
                : null,
        ]);

        AdminActivityLog::record($request->user(), 'career.opportunity.updated', $careerOpportunity, [
            'title' => $careerOpportunity->title,
            'employer' => $careerOpportunity->employer,
            'published' => $careerOpportunity->is_published,
        ]);

        if (! $wasPublished && $shouldPublish) {
            $this->notifyAlumni($careerOpportunity);
        }

        return back()->with('saved', $shouldPublish
            ? 'Career opportunity updated and published.'
            : 'Career opportunity saved as a draft.');
    }

    public function destroy(Request $request, CareerOpportunity $careerOpportunity): RedirectResponse
    {
        $title = $careerOpportunity->title;

        AdminActivityLog::record($request->user(), 'career.opportunity.deleted', $careerOpportunity, [
            'title' => $title,
            'employer' => $careerOpportunity->employer,
        ]);

        $careerOpportunity->delete();

        return back()->with('saved', "Career opportunity {$title} was removed.");
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request, ?string $errorBag = null): array
    {
        $safeText = ['not_regex:/[<>]/u'];

        $rules = [
            'title' => ['required', 'string', 'max:160', ...$safeText],
            'employer' => ['required', 'string', 'max:160', ...$safeText],
            'location' => ['nullable', 'string', 'max:160', ...$safeText],
            'employment_type' => ['nullable', Rule::in(array_keys(CareerOpportunity::employmentTypes()))],
            'description' => ['required', 'string', 'max:5000', ...$safeText],
            'requirements' => ['nullable', 'string', 'max:5000', ...$safeText],
            'application_url' => ['nullable', 'url:http,https', 'max:2048'],
            'application_email' => ['nullable', 'email', 'max:255'],
            'application_deadline' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ];

        return $errorBag
            ? $request->validateWithBag($errorBag, $rules)
            : $request->validate($rules);
    }

    private function notifyAlumni(CareerOpportunity $opportunity): void
    {
        // Career notifications only go to explicit alumni accounts, not trainees.
        $alumni = User::query()->where('role', 'alumni')->get();

        if ($alumni->isNotEmpty()) {
            Notification::send($alumni, new CareerOpportunityPublished($opportunity));
        }
    }
}
