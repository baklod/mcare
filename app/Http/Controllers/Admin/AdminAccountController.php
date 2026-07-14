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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    public function index(): View
    {
        return view('admin.accounts', [
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'accounts' => User::query()
                ->whereIn('role', ['trainer', 'trainee'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeTrainer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()],
        ]);

        $trainer = User::create([
            ...$validated,
            'role' => 'trainer',
            'applicant_status' => 'staff_created',
        ]);

        AdminActivityLog::record($request->user(), 'admin.trainer.created', $trainer, [
            'email' => $trainer->email,
        ]);

        return back()->with('saved', "Trainer account created for {$trainer->name}.");
    }

    public function storeTrainee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:enrollment_applications,email'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'contact_number' => ['required', 'string', 'max:30'],
            'schedule_preference' => ['required', Rule::in(['AM', 'PM'])],
            'street' => ['required', 'string', 'max:180'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'zip_code' => ['required', 'string', 'max:20'],
            'educational_attainment' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180'],
            'year_graduated' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
        ]);

        [$trainee, $application] = DB::transaction(function () use ($request, $validated) {
            $trainee = User::create([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'trainee',
                'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
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

        return back()->with('saved', "Trainee account created for {$trainee->name}.");
    }
}
