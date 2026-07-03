<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        $application = null;

        if ($request->user()) {
            $application = EnrollmentApplication::where('user_id', $request->user()->id)->latest()->first();
        }

        return view('enrollment.create', [
            'application' => $application,
            'user' => $request->user(),
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
        $currentApplication = $currentUser
            ? EnrollmentApplication::where('user_id', $currentUser->id)->first()
            : null;
        $safeText = ["not_regex:/[<>\"'`;{}|\\\\]/u"];
        $safeOptionalText = ["nullable", "string", "max:120", "not_regex:/[<>\"'`;{}|\\\\]/u"];
        $blockedPasswordCharacters = "not_regex:/[<>\"'`;{}|\\\\]/u";
        $documentRules = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

        $validated = $request->validate([
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'ends_with:@gmail.com',
                Rule::unique('users', 'email')->ignore($currentUser?->id),
            ],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), $blockedPasswordCharacters],
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
            'street' => ['required', 'string', 'max:180', ...$safeText],
            'barangay' => ['required', 'string', 'max:120', ...$safeText],
            'city' => ['required', 'string', 'max:120', ...$safeText],
            'province' => ['required', 'string', 'max:120', ...$safeText],
            'region' => ['required', 'string', 'max:120', ...$safeText],
            'zip_code' => ['required', 'string', 'max:20', 'regex:/\A[\pL\pN\s-]+\z/u'],
            'educational_attainment' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180', ...$safeText],
            'year_graduated' => ['required', 'integer', 'min:1950', 'max:' . now()->year],
            'guardian_name' => ['required', 'string', 'max:180', ...$safeText],
            'guardian_address' => ['required', 'string', 'max:255', ...$safeText],
            'classification' => ['nullable', 'string', 'max:120'],
            'disability_type' => ['nullable', 'string', 'max:120'],
            'disability_cause' => ['nullable', 'string', 'max:120'],
            'scholarship_type' => ['nullable', 'string', 'max:120', ...$safeText],
            'privacy_consent' => ['accepted'],
            'signature_name' => ['required', 'string', 'max:180', ...$safeText],
            'birth_certificate' => [$currentApplication?->birth_certificate_path ? 'nullable' : 'required', ...$documentRules],
            'education_document' => [$currentApplication?->education_document_path ? 'nullable' : 'required', ...$documentRules],
            'good_moral_certificate' => [$currentApplication?->good_moral_certificate_path ? 'nullable' : 'required', ...$documentRules],
            'id_photo' => [$currentApplication?->id_photo_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'signature_type' => ['required', 'in:draw,upload'],
            'signature_data' => ['exclude_unless:signature_type,draw', $currentApplication?->signature_path ? 'nullable' : 'required', 'string'],
            'signature_upload' => ['exclude_unless:signature_type,upload', $currentApplication?->signature_path ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'email.ends_with' => 'Please use a Gmail address ending in @gmail.com.',
            'email.unique' => 'This Gmail account already has an applicant account. Please sign in or use a different Gmail address.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'password.not_regex' => 'Password cannot contain <, >, quotes, backticks, semicolons, braces, pipes, or backslashes.',
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

        $user = $currentUser ?? new User();
        $user->forceFill(
            [
                'email' => $validated['email'],
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'role' => 'applicant',
                'applicant_status' => 'profile_submitted',
                'password' => $validated['password'],
            ],
        )->save();

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
                'privacy_consent' => true,
                'date_accomplished' => now()->toDateString(),
                'status' => 'profile_submitted',
            ])
            ->merge($documentPaths)
            ->all();

        EnrollmentApplication::updateOrCreate(
            ['user_id' => $user->id],
            $applicationData,
        );

        Auth::login($user);

        return redirect()
            ->route('enrollment.create')
            ->with('saved', 'Caregiving NC II enrollment registration saved. Your documents and signature are ready for admin review.');
    }

    private function storeUploadedDocument(Request $request, string $field, User $user, ?string $existingPath): ?string
    {
        if (! $request->hasFile($field)) {
            return $existingPath;
        }

        return $request->file($field)->store("enrollment-documents/{$user->id}", 'local');
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
}
