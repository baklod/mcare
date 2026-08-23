<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        $application = null;

        if ($request->user()) {
            $application = EnrollmentApplication::where('user_id', $request->user()->id)->latest()->first();
        }

        $enrollmentBatch = $application?->batch ?: TrainingBatch::openForEnrollment();
        $documentLabels = [
            'birth-certificate' => 'Birth Certificate',
            'education-document' => 'Form 137/138 or Diploma',
            'good-moral-certificate' => 'Good Moral Certificate',
            'id-photo' => 'ID Photo',
            'signature' => 'E-Signature',
        ];
        $documentFeedback = collect($application?->document_review ?? [])
            ->filter(fn ($item) => in_array(data_get($item, 'status'), ['replace', 'missing'], true));

        return view('enrollment.create', [
            'application' => $application,
            'enrollmentBatch' => $enrollmentBatch,
            'user' => $request->user(),
            'googleIdentity' => $this->googleIdentity($request),
            'isGoogleApplicant' => filled($request->user()?->google_id),
            'documentLabels' => $documentLabels,
            'documentFeedback' => $documentFeedback,
            'draftUploads' => $request->session()->get('enrollment.draft_uploads', []),
        ]);
    }

    public function draftContent(Request $request, string $field): BinaryFileResponse
    {
        abort_unless(in_array($field, $this->uploadFields(), true), 404);

        $draft = data_get($request->session()->get('enrollment.draft_uploads', []), $field);
        $path = data_get($draft, 'path');

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $name = basename((string) data_get($draft, 'name', $field));
        $mime = (string) data_get($draft, 'mime', 'application/octet-stream');

        return response()
            ->file(Storage::disk('local')->path($path), [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(
            collect($request->all())
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->merge(['email' => strtolower(trim($request->input('email', '')))])
                ->all()
        );

        $currentUser = $request->user();
        if ($currentUser) {
            // A signed-in identity owns its account email. Ignore a tampered
            // form value instead of allowing enrollment to relink the account.
            $request->merge(['email' => Str::lower($currentUser->email)]);
        }

        $currentApplication = $currentUser
            ? EnrollmentApplication::where('user_id', $currentUser->id)->first()
            : null;
        $enrollmentBatch = $currentApplication?->batch ?: TrainingBatch::openForEnrollment();
        $isGoogleApplicant = filled($currentUser?->google_id);
        $passwordRules = $isGoogleApplicant
            ? ['exclude']
            : [
                $currentUser ? 'nullable' : 'required',
                'confirmed',
                'max:255',
                Password::min(10)->mixedCase()->letters()->numbers(),
            ];

        // Existing applicants may update their record; only new entries require an open window.
        if (! $currentApplication && ! $enrollmentBatch) {
            throw ValidationException::withMessages([
                'training_batch' => 'Enrollment is currently closed. Please wait for the next batch enrollment window.',
            ]);
        }
        $safeText = ["not_regex:/[<>\"'`;{}|\\\\]/u"];
        $safeOptionalText = ['nullable', 'string', 'max:120', "not_regex:/[<>\"'`;{}|\\\\]/u"];
        $documentRules = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'];
        $draftUploads = $request->session()->get('enrollment.draft_uploads', []);
        $hasDocument = fn (string $field, ?string $existingPath): bool => filled($existingPath)
            || filled(data_get($draftUploads, "{$field}.path"));

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'ends_with:@gmail.com',
                Rule::unique('users', 'email')->ignore($currentUser?->id),
            ],
            'password' => $passwordRules,
            'first_name' => ['required', 'string', 'max:100', ...$safeText],
            'middle_name' => ['nullable', 'string', 'max:100', ...$safeText],
            'last_name' => ['required', 'string', 'max:100', ...$safeText],
            'extension_name' => ['nullable', 'string', 'max:30', ...$safeText],
            'birth_date' => ['required', 'date'],
            'birthplace_city' => $safeOptionalText,
            'birthplace_province' => $safeOptionalText,
            'birthplace_region' => $safeOptionalText,
            'gender' => ['required', 'in:Female,Male'],
            'civil_status' => ['required', 'string', 'max:50'],
            'employment_status' => ['required', 'string', 'max:80'],
            'employment_type' => ['nullable', 'string', 'max:80'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+\s().-]+\z/'],
            'nationality' => ['required', 'string', 'max:80', ...$safeText],
            'schedule_preference' => ['required', 'in:AM,PM,Weekend'],
            'street' => ['required', 'string', 'max:100', ...$safeText],
            'barangay' => ['required', 'string', 'max:120', ...$safeText],
            'city' => ['required', 'string', 'max:120', ...$safeText],
            'province' => ['required', 'string', 'max:120', ...$safeText],
            'region' => ['required', 'string', 'max:120', ...$safeText],
            'zip_code' => ['required', 'string', 'max:20', 'regex:/\A[\pL\pN\s-]+\z/u'],
            'educational_attainment' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180', ...$safeText],
            'year_graduated' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'guardian_name' => ['required', 'string', 'max:180', ...$safeText],
            'guardian_address' => ['required', 'string', 'max:255', ...$safeText],
            'classification' => ['nullable', 'string', 'max:120'],
            'disability_type' => ['nullable', 'string', 'max:120'],
            'disability_cause' => ['nullable', 'string', 'max:120'],
            'scholarship_type' => ['nullable', 'string', 'max:120', ...$safeText],
            'privacy_consent' => ['accepted'],
            'signature_name' => ['required', 'string', 'max:180', ...$safeText],
            'birth_certificate' => [$hasDocument('birth_certificate', $currentApplication?->birth_certificate_path) ? 'nullable' : 'required', ...$documentRules],
            'education_document' => [$hasDocument('education_document', $currentApplication?->education_document_path) ? 'nullable' : 'required', ...$documentRules],
            'good_moral_certificate' => [$hasDocument('good_moral_certificate', $currentApplication?->good_moral_certificate_path) ? 'nullable' : 'required', ...$documentRules],
            'id_photo' => [$hasDocument('id_photo', $currentApplication?->id_photo_path) ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
            'signature_type' => ['required', 'in:draw,upload'],
            'signature_data' => ['exclude_unless:signature_type,draw', $currentApplication?->signature_path ? 'nullable' : 'required', 'string'],
            'signature_upload' => ['exclude_unless:signature_type,upload', $hasDocument('signature_upload', $currentApplication?->signature_path) ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
        ], [
            'email.ends_with' => 'Please use a Gmail address ending in @gmail.com.',
            'email.unique' => 'This Gmail account already has an applicant account. Please sign in or use a different Gmail address.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'password.confirmed' => 'Password and confirmation must match.',
            'birth_certificate.required' => 'Upload a clear birth certificate copy.',
            'education_document.required' => 'Upload Form 137/138 or diploma.',
            'good_moral_certificate.required' => 'Upload a certificate of good moral.',
            'id_photo.required' => 'Upload a 1x1 or 2x2 ID photo.',
            'signature_data.required' => 'Draw your signature before submitting.',
            'signature_upload.required' => 'Upload a signature image or choose Draw Signature.',
            '*.mimes' => 'Accepted formats are PDF, JPG, JPEG, and PNG. ID photo and signature image must be JPG or PNG.',
            '*.max' => 'Each uploaded file must not exceed 5MB.',
        ]);

        if ($validator->fails()) {
            // Keep only files that passed their own validation in a private session draft.
            $this->preserveValidUploads($request, $validator->errors()->keys());

            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $user = $currentUser ?? new User;
        $userData = [
            'email' => $validated['email'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'role' => 'applicant',
            'applicant_status' => 'profile_submitted',
        ];

        if (filled($validated['password'] ?? null)) {
            $userData['password'] = $validated['password'];
        }

        $user->forceFill($userData)->save();

        $documentPaths = [
            'birth_certificate_path' => $this->storeUploadedDocument($request, 'birth_certificate', $user, $currentApplication?->birth_certificate_path),
            'education_document_path' => $this->storeUploadedDocument($request, 'education_document', $user, $currentApplication?->education_document_path),
            'good_moral_certificate_path' => $this->storeUploadedDocument($request, 'good_moral_certificate', $user, $currentApplication?->good_moral_certificate_path),
            'id_photo_path' => $this->storeUploadedDocument($request, 'id_photo', $user, $currentApplication?->id_photo_path),
            'signature_type' => $validated['signature_type'],
            'signature_path' => $this->storeSignature($request, $user, $currentApplication?->signature_path),
        ];

        $applicationData = collect($validated)
            ->except([
                'password',
                'password_confirmation',
                'birth_certificate',
                'education_document',
                'good_moral_certificate',
                'id_photo',
                'signature_data',
                'signature_upload',
            ])
            ->merge([
                'user_id' => $user->id,
                'program' => 'Caregiving NC II',
                'training_batch_id' => $currentApplication?->training_batch_id ?: $enrollmentBatch?->id,
                'privacy_consent' => true,
                'date_accomplished' => now()->toDateString(),
                'status' => 'profile_submitted',
            ])
            ->merge($documentPaths)
            ->all();

        $application = EnrollmentApplication::updateOrCreate(
            ['user_id' => $user->id],
            $applicationData,
        );

        $this->clearDraftUploads($request);
        $request->session()->forget('enrollment.google_identity');

        if ($currentApplication) {
            $review = $application->document_review ?? [];
            $replacements = [
                'birth-certificate' => $request->hasFile('birth_certificate'),
                'education-document' => $request->hasFile('education_document'),
                'good-moral-certificate' => $request->hasFile('good_moral_certificate'),
                'id-photo' => $request->hasFile('id_photo'),
                'signature' => $request->hasFile('signature_upload') || filled($request->input('signature_data')),
            ];

            // A replacement must return to the admin queue instead of retaining an old acceptance/problem result.
            foreach ($replacements as $document => $wasReplaced) {
                if ($wasReplaced) {
                    $review[$document] = [
                        'status' => 'unreviewed',
                        'note' => 'Replacement uploaded; awaiting admin review.',
                    ];
                }
            }

            $application->forceFill(['document_review' => $review])->save();
        }

        // Keep payment continuation private to this browser session. Creating
        // an applicant record must not silently sign the person into the
        // public account bar; explicit login remains the account boundary.
        if (! $currentUser) {
            $request->session()->put('enrollment.payment_application_id', $application->id);
        }

        if (! $currentUser && ! $user->hasVerifiedEmail()) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            $user->notify(new EnrollmentSubmittedNotification($application));
        } catch (Throwable $exception) {
            // SMTP delivery must not roll back a valid enrollment submission.
            report($exception);
        }

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', 'Caregiving NC II enrollment registration saved. Choose your payment method to continue.');
    }

    /** @return array{email: string, first_name: string, middle_name: string, last_name: string, full_name: string, avatar_url: ?string} */
    private function googleIdentity(Request $request): array
    {
        $user = $request->user();

        if (! $user || blank($user->google_id)) {
            return [
                'email' => '',
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'full_name' => '',
                'avatar_url' => null,
            ];
        }

        $identity = $request->session()->get('enrollment.google_identity', []);

        if (is_array($identity) && Str::lower((string) ($identity['email'] ?? '')) === Str::lower($user->email)) {
            return [
                'email' => $user->email,
                'first_name' => trim((string) ($identity['first_name'] ?? '')),
                'middle_name' => trim((string) ($identity['middle_name'] ?? '')),
                'last_name' => trim((string) ($identity['last_name'] ?? '')),
                'full_name' => trim((string) ($identity['full_name'] ?? $user->name)),
                'avatar_url' => $identity['avatar_url'] ?? $user->avatar_url,
            ];
        }

        $parts = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = (string) array_shift($parts);
        $lastName = count($parts) > 0 ? (string) array_pop($parts) : '';

        return [
            'email' => $user->email,
            'first_name' => $firstName,
            'middle_name' => implode(' ', $parts),
            'last_name' => $lastName,
            'full_name' => (string) $user->name,
            'avatar_url' => $user->avatar_url,
        ];
    }

    private function storeUploadedDocument(Request $request, string $field, User $user, ?string $existingPath): ?string
    {
        if ($request->hasFile($field)) {
            $this->forgetDraftUpload($request, $field);

            return $request->file($field)->store("enrollment-documents/{$user->id}", 'local');
        }

        return $this->moveDraftUpload($request, $field, $user) ?: $existingPath;
    }

    private function storeSignature(Request $request, User $user, ?string $existingPath): ?string
    {
        if ($request->input('signature_type') === 'upload') {
            return $this->storeUploadedDocument($request, 'signature_upload', $user, $existingPath);
        }

        $signatureData = $request->input('signature_data');

        if (! $signatureData && $existingPath) {
            return $existingPath;
        }

        if (! is_string($signatureData) || ! str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'signature_data' => 'Draw your signature before submitting.',
            ]);
        }

        $decodedSignature = base64_decode(substr($signatureData, strlen('data:image/png;base64,')), true);

        if (! $decodedSignature || strlen($decodedSignature) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature_data' => 'The drawn signature could not be saved. Please clear and draw it again.',
            ]);
        }

        $path = "enrollment-documents/{$user->id}/signature-".now()->format('YmdHis').'.png';
        Storage::disk('local')->put($path, $decodedSignature);

        return $path;
    }

    /** @return array<int, string> */
    private function uploadFields(): array
    {
        return [
            'birth_certificate',
            'education_document',
            'good_moral_certificate',
            'id_photo',
            'signature_upload',
        ];
    }

    /** @param array<int, string> $invalidFields */
    private function preserveValidUploads(Request $request, array $invalidFields): void
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $directory = 'enrollment-drafts/'.hash('sha256', $request->session()->getId());

        foreach ($this->uploadFields() as $field) {
            if (! $request->hasFile($field) || in_array($field, $invalidFields, true)) {
                continue;
            }

            if ($oldPath = data_get($drafts, "{$field}.path")) {
                Storage::disk('local')->delete($oldPath);
            }

            $file = $request->file($field);
            $path = $file->storeAs($directory, Str::random(40).'.'.$file->extension(), 'local');
            $drafts[$field] = [
                'path' => $path,
                'name' => basename($file->getClientOriginalName()),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ];
        }

        $request->session()->put('enrollment.draft_uploads', $drafts);
    }

    private function moveDraftUpload(Request $request, string $field, User $user): ?string
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $source = data_get($drafts, "{$field}.path");

        if (! is_string($source) || ! Storage::disk('local')->exists($source)) {
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $destination = "enrollment-documents/{$user->id}/".Str::random(40).($extension ? '.'.$extension : '');
        Storage::disk('local')->move($source, $destination);
        unset($drafts[$field]);
        $request->session()->put('enrollment.draft_uploads', $drafts);

        return $destination;
    }

    private function forgetDraftUpload(Request $request, string $field): void
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $path = data_get($drafts, "{$field}.path");

        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }

        unset($drafts[$field]);
        $request->session()->put('enrollment.draft_uploads', $drafts);
    }

    private function clearDraftUploads(Request $request): void
    {
        foreach ($request->session()->get('enrollment.draft_uploads', []) as $draft) {
            if ($path = data_get($draft, 'path')) {
                Storage::disk('local')->delete($path);
            }
        }

        $request->session()->forget('enrollment.draft_uploads');
    }
}
