<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AlumniProfile;
use App\Models\EnrollmentApplication;
use App\Models\Quiz;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class AdminAccountController extends Controller
{
    public function index(Request $request): View
    {
        $roleFilter = $request->query('role', 'all');
        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->whereIn('role', ['trainer', 'trainee', 'applicant'])
            ->with(['enrollmentApplication.batch', 'roles'])
            ->when($roleFilter !== 'all' && in_array($roleFilter, ['trainer', 'trainee', 'applicant'], true), function ($q) use ($roleFilter) {
                $q->where('role', $roleFilter);
            })
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('enrollmentApplication', function ($appQ) use ($search) {
                            $appQ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('contact_number', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $accounts = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => User::query()->whereIn('role', ['trainer', 'trainee', 'applicant'])->count(),
            'trainer' => User::query()->where('role', 'trainer')->count(),
            'trainee' => User::query()->where('role', 'trainee')->count(),
            'applicant' => User::query()->where('role', 'applicant')->count(),
        ];

        return view('admin.accounts', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'accounts' => $accounts,
            'roleFilter' => $roleFilter,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function photo(User $user): BinaryFileResponse
    {
        abort_unless(in_array($user->role, ['trainer', 'trainee', 'applicant'], true), 404);

        $path = $user->enrollmentApplication?->id_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $mime = Storage::disk('local')->mimeType($path) ?: 'application/octet-stream';
        abort_unless(str_starts_with($mime, 'image/'), 404);

        $filename = basename($path);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
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
        abort_unless(in_array($user->role, ['trainer', 'trainee', 'applicant'], true), 404);

        if ($user->id === $request->user()->id || $user->hasRole('admin') || $user->role === 'admin') {
            return back()->withErrors([
                'account' => 'Administrator accounts cannot be deleted here.',
            ]);
        }

        $accountLabel = $user->name ?: 'Applicant';
        $accountEmail = $user->email;
        $accountRole = $user->role;

        // 1. Gather all file paths from storage before DB deletion
        $application = $user->enrollmentApplication()->first();
        $storageFiles = collect();

        if ($application) {
            $storageFiles = $storageFiles->merge([
                $application->birth_certificate_path,
                $application->education_document_path,
                $application->good_moral_certificate_path,
                $application->id_photo_path,
                $application->signature_path,
            ])->filter();

            $storageFiles = $storageFiles->merge(
                $application->paymentTransactions()->pluck('receipt_proof_path')->filter()
            );
        }

        $officialFiles = $application
            ? $application->officialDocuments()
                ->whereNotNull('file_path')
                ->get(['storage_disk', 'file_path'])
            : collect();

        $queuedJobIds = $this->queuedNotificationJobIds($user);

        // 2. Perform transactional deletion of all related database records
        DB::transaction(function () use ($user, $application, $request, $accountRole, $accountEmail, $queuedJobIds): void {
            if ($queuedJobIds !== []) {
                DB::table('jobs')->whereIn('id', $queuedJobIds)->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $user->id)
                    ->delete();
            }

            if ($application) {
                // Clean up related child rows
                $application->officialDocuments()->delete();
                $application->competencyRecords()->delete();
                $application->quizAttempts()->delete();
                $application->moduleProgress()->delete();
                $application->paymentTransactions()->delete();
                $application->paymentAttempts()->delete();
                $application->targetedQuizzes()->update(['target_enrollment_application_id' => null]);
                TrainingModule::where('target_enrollment_application_id', $application->id)->update(['target_enrollment_application_id' => null]);

                $application->delete();
            }

            if ($accountRole === 'trainer') {
                TrainingBatch::where('trainer_id', $user->id)->update(['trainer_id' => null]);
                TrainerAnnouncement::where('trainer_id', $user->id)->delete();
                Quiz::where('trainer_id', $user->id)->delete();
                TrainingModule::where('trainer_id', $user->id)->delete();
            }

            AlumniProfile::where('user_id', $user->id)->delete();

            AdminActivityLog::record($request->user(), 'admin.account.deleted', $user, [
                'deleted_role' => $accountRole,
                'deleted_email' => $accountEmail,
            ]);

            $user->syncRoles([]);
            $user->delete();
        });

        // 3. Delete physical files from storage
        foreach ($storageFiles->unique() as $path) {
            Storage::disk('local')->delete($path);
        }
        foreach ($officialFiles as $doc) {
            Storage::disk($doc->storage_disk ?: 'local')->delete($doc->file_path);
        }

        return back()->with('saved', "Account for {$accountLabel} ({$accountEmail}) and related records were permanently removed. The applicant can now submit a fresh enrollment if needed.");
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

    /** @return list<int> */
    private function queuedNotificationJobIds(User $user): array
    {
        if (! Schema::hasTable('jobs')) {
            return [];
        }

        return DB::table('jobs')
            ->get(['id', 'payload'])
            ->filter(function (object $job) use ($user): bool {
                $payload = json_decode($job->payload, true);
                if (data_get($payload, 'data.commandName') !== SendQueuedNotifications::class) {
                    return false;
                }

                try {
                    $command = unserialize((string) data_get($payload, 'data.command'));
                } catch (Throwable) {
                    return false;
                }

                return $command instanceof SendQueuedNotifications
                    && collect($command->notifiables)->contains(
                        fn ($notifiable): bool => $notifiable instanceof User && $notifiable->is($user),
                    );
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }
}
