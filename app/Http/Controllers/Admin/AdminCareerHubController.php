<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AlumniProfile;
use App\Models\CareerOpportunity;
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
            'alumniAccounts' => User::query()->where('role', 'alumni')->count(),
            'availableAlumni' => AlumniProfile::query()
                ->where('is_available_for_duty', true)
                ->whereHas('user', fn ($query) => $query->where('role', 'alumni'))
                ->count(),
            'publishedJobs' => CareerOpportunity::query()->visibleToAlumni()->count(),
            'draftJobs' => CareerOpportunity::query()->where('is_published', false)->count(),
            'jobs' => CareerOpportunity::query()
                ->with('creator')
                ->latest('created_at')
                ->paginate(12),
            'alumniRoster' => User::query()
                ->where('role', 'alumni')
                ->with('alumniProfile')
                ->orderBy('name')
                ->simplePaginate(10, ['*'], 'alumni_page'),
            'patientGenders' => CareerOpportunity::patientGenders(),
            'mobilityStatuses' => CareerOpportunity::mobilityStatuses(),
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
                ->orderBy('estimated_start_date')
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => 0,
            'alumniProfile' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedPayload($request, 'careerCreate');
        $shouldPublish = $request->boolean('is_published');

        $opportunity = CareerOpportunity::create([
            ...$validated,
            ...$this->compatibilityFields($validated),
            'created_by_id' => $request->user()->id,
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish ? now() : null,
        ]);

        AdminActivityLog::record($request->user(), 'career.opportunity.created', $opportunity, [
            'estimated_start_date' => $opportunity->estimated_start_date?->toDateString(),
            'patient_gender' => $opportunity->patient_gender,
            'mobility_status' => $opportunity->mobility_status,
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

        $careerOpportunity->fill([
            ...$validated,
            ...$this->compatibilityFields($validated),
            'is_published' => $shouldPublish,
            'published_at' => $shouldPublish
                ? ($careerOpportunity->published_at ?: now())
                : null,
        ]);

        // Retire legacy contact and free-form fields when an old listing is reviewed.
        $careerOpportunity->forceFill([
            'location' => null,
            'employment_type' => null,
            'requirements' => null,
            'application_url' => null,
            'application_email' => null,
            'application_deadline' => null,
        ])->save();

        AdminActivityLog::record($request->user(), 'career.opportunity.updated', $careerOpportunity, [
            'estimated_start_date' => $careerOpportunity->estimated_start_date?->toDateString(),
            'patient_gender' => $careerOpportunity->patient_gender,
            'mobility_status' => $careerOpportunity->mobility_status,
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
        $label = 'duty starting '.($careerOpportunity->estimated_start_date?->format('M d, Y') ?? 'on an unset date');

        AdminActivityLog::record($request->user(), 'career.opportunity.deleted', $careerOpportunity, [
            'estimated_start_date' => $careerOpportunity->estimated_start_date?->toDateString(),
        ]);

        $careerOpportunity->delete();

        return back()->with('saved', "Caregiving {$label} was removed.");
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request, ?string $errorBag = null): array
    {
        $safeText = ['not_regex:/[<>]/u'];

        $rules = [
            'estimated_start_date' => ['required', 'date', 'after_or_equal:today'],
            'patient_gender' => ['required', Rule::in(array_keys(CareerOpportunity::patientGenders()))],
            'mobility_status' => ['required', Rule::in(array_keys(CareerOpportunity::mobilityStatuses()))],
            'patient_age' => ['nullable', 'integer', 'between:0,120', 'required_without_all:specific_contraptions,condition_summary'],
            'specific_contraptions' => ['nullable', 'string', 'max:255', 'required_without_all:patient_age,condition_summary', ...$safeText],
            'condition_summary' => ['nullable', 'string', 'max:500', 'required_without_all:patient_age,specific_contraptions', ...$safeText],
            'is_published' => ['nullable', 'boolean'],
        ];

        return $errorBag
            ? $request->validateWithBag($errorBag, $rules)
            : $request->validate($rules);
    }

    /** @param array<string, mixed> $payload */
    private function compatibilityFields(array $payload): array
    {
        $gender = CareerOpportunity::patientGenders()[$payload['patient_gender']];
        $mobility = CareerOpportunity::mobilityStatuses()[$payload['mobility_status']];

        return [
            'title' => "Caregiving Duty - {$gender}, {$mobility}",
            'employer' => 'MCARE-Coordinated Placement',
            'description' => 'Privacy-minimal duty posting managed through the MCARE Alumni Hub.',
        ];
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
