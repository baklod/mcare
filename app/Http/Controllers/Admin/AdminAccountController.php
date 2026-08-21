<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class AdminAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.accounts', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'accounts' => User::query()
                ->whereIn('role', ['trainer', 'trainee'])
                ->with('enrollmentApplication')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeTrainer(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validateWithBag('trainer', [
            'name' => ['required', 'string', 'max:255', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('enrollment_applications', 'email'),
            ],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
        ], [
            'name.regex' => 'Use letters, spaces, periods, apostrophes, or hyphens only for the name.',
            'email.unique' => 'This email is already connected to an MCARE account or enrollment.',
        ]);

        $trainer = User::create([
            ...$validated,
            'role' => 'trainer',
            'applicant_status' => 'staff_created',
        ]);

        AdminActivityLog::record($request->user(), 'admin.trainer.created', $trainer, [
            'email' => $trainer->email,
        ]);

        return $this->accountCreatedResponse($request, $trainer, "Trainer account created for {$trainer->name}.");
    }

    public function storeTrainee(Request $request): RedirectResponse
    {
        $request->merge([
            'first_name' => trim((string) $request->input('first_name')),
            'middle_name' => filled($request->input('middle_name')) ? trim((string) $request->input('middle_name')) : null,
            'last_name' => trim((string) $request->input('last_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'contact_number' => trim((string) $request->input('contact_number')),
        ]);

        $validated = $request->validateWithBag('trainee', [
            'first_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('enrollment_applications', 'email'),
            ],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()->symbols()],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+(). \-]{7,30}\z/'],
            'schedule_preference' => ['required', Rule::in(['AM', 'PM'])],
            'street' => ['required', 'string', 'max:180'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'zip_code' => ['required', 'string', 'max:20'],
            'educational_attainment' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180'],
            'year_graduated' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
        ], [
            '*.regex' => 'Use letters, spaces, periods, apostrophes, or hyphens only for names and a valid phone format for contact number.',
            'email.unique' => 'This email is already connected to an MCARE account or enrollment.',
        ]);

        [$trainee, $application] = DB::transaction(function () use ($request, $validated) {
            $trainee = User::create([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'trainee',
                'applicant_status' => 'staff_created',
            ]);

            $application = EnrollmentApplication::create([
                ...collect($validated)->except(['password', 'password_confirmation'])->all(),
                'user_id' => $trainee->id,
                'program' => 'Caregiving NC II',
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'privacy_consent' => false,
                'date_accomplished' => today(),
                'reviewed_at' => now(),
                'reviewed_by_id' => $request->user()->id,
                'admin_notes' => 'Trainee account created by an administrator.',
            ]);

            return [$trainee, $application];
        });

        AdminActivityLog::record($request->user(), 'admin.trainee.created', $application, [
            'user_id' => $trainee->id,
            'email' => $trainee->email,
        ]);

        return $this->accountCreatedResponse($request, $trainee, "Trainee account created for {$trainee->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, ['trainer', 'trainee'], true), 404);

        if (! $this->wasCreatedByAdmin($user)) {
            return back()->withErrors([
                'account' => 'Only Trainer or Trainee accounts created by an administrator can be removed here.',
            ]);
        }

        $blockers = $this->deletionBlockers($user);
        if ($blockers !== []) {
            return back()->withErrors([
                'account' => 'This account is protected because it already has related records: '.implode(', ', $blockers).'.',
            ]);
        }

        $accountLabel = $user->name;
        AdminActivityLog::record($request->user(), 'admin.account.deleted', $user, [
            'deleted_role' => $user->role,
            'deleted_email' => $user->email,
        ]);

        DB::transaction(function () use ($user): void {
            // Clean non-FK session/notification rows before the user cascade.
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }
            $user->syncRoles([]);
            $user->delete();
        });

        return back()->with('saved', "Account for {$accountLabel} was safely removed.");
    }

    private function accountCreatedResponse(Request $request, User $user, string $message): RedirectResponse
    {
        $verificationSent = true;

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
            $verificationSent = false;
            AdminActivityLog::record($request->user(), 'admin.account.email-verification.failed', $user, [
                'email' => $user->email,
            ]);
        }

        return back()
            ->with('saved', $message)
            ->with('verification_notice', $verificationSent
                ? "A verification link was sent to {$user->email}."
                : 'The account was created, but the verification email could not be sent. Check the mail configuration and resend later.');
    }

    private function wasCreatedByAdmin(User $user): bool
    {
        if ($user->applicant_status === 'staff_created') {
            return true;
        }

        return $user->role === 'trainee'
            && $user->enrollmentApplication()
                ->whereNotNull('reviewed_by_id')
                ->where('admin_notes', 'Trainee account created by an administrator.')
                ->exists();
    }

    /** @return list<string> */
    private function deletionBlockers(User $user): array
    {
        $blockers = [];

        if ($user->role === 'trainer') {
            if ($user->trainingModules()->exists()) $blockers[] = 'learning modules';
            if ($user->trainerAnnouncements()->exists()) $blockers[] = 'announcements';
            if ($user->quizzes()->exists()) $blockers[] = 'quizzes';

            return $blockers;
        }

        $application = $user->enrollmentApplication()->first();
        if (! $application) return $blockers;

        if ($application->moduleProgress()->exists()) $blockers[] = 'module progress';
        if ($application->quizAttempts()->exists()) $blockers[] = 'quiz attempts';
        if ($application->competencyRecords()->exists()) $blockers[] = 'competency records';
        if ($application->officialDocuments()->exists()) $blockers[] = 'official documents';
        if ($application->paymentAttempts()->exists()) $blockers[] = 'payment attempts';
        if ($application->payment_status !== EnrollmentApplication::PAYMENT_NOT_SELECTED) $blockers[] = 'payment history';
        if ($application->learning_status === EnrollmentApplication::LEARNING_GRADUATED) $blockers[] = 'graduation records';

        foreach (['birth_certificate_path', 'education_document_path', 'good_moral_certificate_path', 'id_photo_path', 'signature_path'] as $path) {
            if (filled($application->{$path})) {
                $blockers[] = 'uploaded enrollment files';
                break;
            }
        }

        return $blockers;
    }
}
