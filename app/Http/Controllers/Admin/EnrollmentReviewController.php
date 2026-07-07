<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        ]);

        $selectedStatus = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        $applicationsQuery = EnrollmentApplication::query()
            ->with(['user', 'batch'])
            ->latest();

        if (array_key_exists($selectedStatus, $statuses)) {
            $applicationsQuery->where('status', $selectedStatus);
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
            'counts' => $counts,
            'reviewableStatuses' => EnrollmentApplication::reviewableStatuses(),
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'statuses' => $statuses,
            'totalApplications' => EnrollmentApplication::count(),
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
