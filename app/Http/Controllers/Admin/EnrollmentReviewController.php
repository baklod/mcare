<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Services\TesdaRegistrationPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentReviewController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = EnrollmentApplication::statuses();

        /*
         * Search values are bounded before they reach LIKE queries. Eloquent
         * parameter binding already protects query values from SQL injection;
         * this validation mainly prevents oversized/abusive search payloads.
         */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'enrollment_state' => ['nullable', Rule::in(['open', 'upcoming', 'closed'])],
            'training_state' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
        ]);

        $selectedStatus = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $batchId = isset($filters['batch_id']) ? (int) $filters['batch_id'] : null;
        $schedule = $filters['schedule'] ?? null;
        $enrollmentState = $filters['enrollment_state'] ?? null;
        $trainingState = $filters['training_state'] ?? null;

        $applicationsQuery = EnrollmentApplication::query()
            ->with(['user', 'batch'])
            ->latest();

        if (array_key_exists($selectedStatus, $statuses)) {
            $applicationsQuery->where('status', $selectedStatus);
        }

        if ($batchId) {
            $applicationsQuery->where('training_batch_id', $batchId);
        }

        if ($schedule) {
            $applicationsQuery->where('schedule_preference', $schedule);
        }

        if ($enrollmentState) {
            $applicationsQuery->whereHas('batch', function ($batchQuery) use ($enrollmentState) {
                match ($enrollmentState) {
                    'open' => $batchQuery
                        ->where('is_active', true)
                        ->where(fn ($query) => $query->whereNull('enrollment_starts_at')->orWhere('enrollment_starts_at', '<=', now()))
                        ->where('enrollment_ends_at', '>', now()),
                    'upcoming' => $batchQuery
                        ->where('is_active', true)
                        ->where('enrollment_starts_at', '>', now()),
                    'closed' => $batchQuery
                        ->where(fn ($query) => $query->where('is_active', false)->orWhere('enrollment_ends_at', '<=', now())),
                };
            });
        }

        if ($trainingState) {
            $applicationsQuery->whereHas('batch', function ($batchQuery) use ($trainingState) {
                match ($trainingState) {
                    'not_started' => $batchQuery
                        ->where(fn ($query) => $query->whereNull('training_starts_at')->orWhere('training_starts_at', '>', now())),
                    'in_progress' => $batchQuery
                        ->where('training_starts_at', '<=', now())
                        ->where(fn ($query) => $query->whereNull('training_ends_at')->orWhere('training_ends_at', '>', now())),
                    'completed' => $batchQuery->where('training_ends_at', '<=', now()),
                };
            });
        }

        if ($search !== '') {
            $applicationsQuery->where(function ($query) use ($search) {
                $query
                    ->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $counts = EnrollmentApplication::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.enrollments.index', [
            'applications' => $applicationsQuery->paginate(10)->withQueryString(),
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'batchId' => $batchId,
            'counts' => $counts,
            'enrollmentState' => $enrollmentState,
            'reviewableStatuses' => EnrollmentApplication::reviewableStatuses(),
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'schedule' => $schedule,
            'statuses' => $statuses,
            'totalApplications' => EnrollmentApplication::count(),
            'trainingState' => $trainingState,
        ]);
    }

    public function show(EnrollmentApplication $enrollmentApplication): View
    {
        return view('admin.enrollments.show', [
            'application' => $enrollmentApplication->load(['user', 'reviewer', 'batch']),
            'reviewableStatuses' => EnrollmentApplication::reviewableStatuses(),
            'statuses' => EnrollmentApplication::statuses(),
        ]);
    }

    public function update(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(EnrollmentApplication::reviewableStatuses())],
            'admin_notes' => [
                Rule::requiredIf($request->input('status') === EnrollmentApplication::STATUS_DENIED),
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'admin_notes.required' => 'Add a clear note before denying an application.',
        ]);

        $enrollmentApplication->forceFill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()->id,
        ])->save();

        $newRole = $validated['status'] === EnrollmentApplication::STATUS_APPROVED
            ? 'trainee'
            : ($enrollmentApplication->user?->role === 'trainee' ? 'applicant' : $enrollmentApplication->user?->role);

        $enrollmentApplication->user?->forceFill([
            'applicant_status' => $validated['status'],
            'role' => $newRole,
        ])->save();

        AdminActivityLog::record($request->user(), 'enrollment.review.updated', $enrollmentApplication, [
            'status' => $validated['status'],
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return redirect()
            ->route('admin.enrollments.show', $enrollmentApplication)
            ->with('saved', 'Enrollment review decision saved.');
    }

    public function tesdaForm(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        TesdaRegistrationPdfService $pdfService,
    ): Response {
        $validated = $request->validate([
            'disposition' => ['nullable', Rule::in(['inline', 'attachment'])],
        ]);
        $disposition = $validated['disposition'] ?? 'inline';

        $enrollmentApplication->loadMissing('batch');
        $pdf = $pdfService->generate($enrollmentApplication);
        $filename = $pdfService->filename($enrollmentApplication);

        AdminActivityLog::record($request->user(), 'enrollment.tesda-form.generated', $enrollmentApplication, [
            'disposition' => $disposition,
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($pdf),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function document(EnrollmentApplication $enrollmentApplication, string $document): StreamedResponse
    {
        $fields = [
            'birth-certificate' => 'birth_certificate_path',
            'education-document' => 'education_document_path',
            'good-moral-certificate' => 'good_moral_certificate_path',
            'id-photo' => 'id_photo_path',
            'signature' => 'signature_path',
        ];

        abort_unless(array_key_exists($document, $fields), 404);

        $path = $enrollmentApplication->{$fields[$document]};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        AdminActivityLog::record(request()->user(), 'enrollment.document.downloaded', $enrollmentApplication, [
            'document' => $document,
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return Storage::disk('local')->download($path);
    }
}
