<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use App\Services\AdminOperationsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class HistoricalAlumniClaimController extends Controller
{
    public function create(): View
    {
        return view('alumni.claim');
    }

    public function store(Request $request, AdminOperationsNotifier $adminNotifier): RedirectResponse
    {
        $request->merge([
            'first_name' => trim((string) $request->input('first_name')),
            'middle_name' => filled($request->input('middle_name')) ? trim((string) $request->input('middle_name')) : null,
            'last_name' => trim((string) $request->input('last_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'contact_number' => trim((string) $request->input('contact_number')),
        ]);

        try {
            $validated = $request->validateWithBag('alumniClaim', [
                'first_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
                'middle_name' => ['nullable', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
                'last_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
                'email' => [
                    'required', 'email:rfc', 'max:255',
                    Rule::unique('users', 'email'),
                    Rule::unique('enrollment_applications', 'email'),
                ],
                'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
                'birth_date' => ['required', 'date', 'before:today'],
                'gender' => ['required', Rule::in(['Male', 'Female'])],
                'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+(). \-]{7,30}\z/'],
                'street' => ['required', 'string', 'max:180'],
                'barangay' => ['required', 'string', 'max:120'],
                'city' => ['required', 'string', 'max:120'],
                'province' => ['required', 'string', 'max:120'],
                'region' => ['required', 'string', 'max:120'],
                'zip_code' => ['required', 'string', 'max:20'],
                'educational_attainment' => ['required', 'string', Rule::in(AdmissionApplication::educationalAttainmentOptions())],
                'school_name' => ['required', 'string', 'max:180'],
                'education_year_graduated' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
                'training_completion_year' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
                'historical_batch_name' => ['nullable', 'string', 'max:120'],
                'training_schedule' => ['required', Rule::in(['AM', 'PM'])],
                'evidence_type' => ['required', Rule::in(['certificate', 'tor', 'both'])],
                'certificate_number' => ['nullable', 'string', 'max:120'],
                'tor_reference' => ['nullable', 'string', 'max:120'],
                'evidence_document' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp', 'max:10240'],
                'evidence_document_page_2' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp', 'max:10240'],
                'privacy_consent' => ['accepted'],
            ], [
                'email.unique' => 'This email is already connected to an MCARE account or enrollment.',
                'password.confirmed' => 'Password and confirmation must match.',
                'educational_attainment.in' => 'Choose an educational attainment from the TESDA registration list.',
                'privacy_consent.accepted' => 'Confirm that MCARE may use these details to verify the historical training record.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('alumni.claim.create'));
        }

        if ($request->hasFile('evidence_document_page_2') && ! $request->hasFile('evidence_document')) {
            return redirect()
                ->route('alumni.claim.create')
                ->withErrors(['evidence_document_page_2' => 'Add evidence page 1 before attaching page 2.'], 'alumniClaim')
                ->withInput();
        }

        $storedPaths = [];

        try {
            [$user, $claim] = DB::transaction(function () use ($request, $validated, &$storedPaths): array {
                $user = User::create([
                    'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'role' => 'applicant',
                    'applicant_status' => 'historical_claim_pending_email',
                ]);

                foreach (['evidence_document', 'evidence_document_page_2'] as $field) {
                    if ($request->hasFile($field)) {
                        $storedPaths[$field] = $request->file($field)->store("historical-alumni-claims/{$user->id}", 'local');
                    }
                }

                $claim = HistoricalAlumniClaim::create([
                    ...collect($validated)->except([
                        'email', 'password', 'password_confirmation', 'privacy_consent',
                        'evidence_document', 'evidence_document_page_2',
                    ])->all(),
                    'user_id' => $user->id,
                    'evidence_document_path' => $storedPaths['evidence_document'] ?? null,
                    'evidence_document_page_2_path' => $storedPaths['evidence_document_page_2'] ?? null,
                    'status' => HistoricalAlumniClaim::STATUS_PENDING_EMAIL,
                    'privacy_consent_at' => now(),
                ]);

                return [$user, $claim];
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_values($storedPaths));
            throw $exception;
        }

        $verificationSent = true;
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
            $verificationSent = false;
        }

        AdminActivityLog::record($request->user(), 'historical-alumni.claim.submitted', $claim, [
            'email' => $user->email,
            'submitted_by_admin' => $request->user()?->hasRole('admin') ?? false,
        ]);

        $adminNotifier->notify(
            title: 'Historical alumni claim submitted',
            message: "{$user->name} submitted a record claim for on-site verification.",
            url: route('admin.historical-alumni.index'),
            icon: 'user-check',
            event: 'historical-alumni.claim.submitted',
            context: ['claim_id' => $claim->id, 'email' => $user->email],
        );

        $request->session()->put('alumni.claim.submitted_id', $claim->id);
        $request->session()->put('alumni.claim.verification_sent', $verificationSent);

        return redirect()
            ->route('alumni.claim.received')
            ->with('claim_submitted', true)
            ->with('claim_name', $user->name)
            ->with('claim_email', $user->email)
            ->with('verification_sent', $verificationSent);
    }

    public function received(Request $request): View|RedirectResponse
    {
        $claimId = $request->session()->get('alumni.claim.submitted_id');
        $claim = is_numeric($claimId)
            ? HistoricalAlumniClaim::query()->with('user')->find((int) $claimId)
            : null;

        if (! $claim) {
            return redirect()->route('alumni.claim.create');
        }

        return view('alumni.claim-received', [
            'name' => $claim->user?->name ?: '',
            'email' => $claim->user?->email ?: '',
            'verificationSent' => (bool) $request->session()->get('alumni.claim.verification_sent', false),
        ]);
    }
}
