<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\TrainingProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingProgramController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $program = TrainingProgram::create($this->validated($request));

        AdminActivityLog::record($request->user(), 'training_program.created', $program, [
            'name' => $program->name,
            'code' => $program->code,
            'is_active' => $program->is_active,
        ]);

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', "Training program {$program->name} created.");
    }

    public function update(Request $request, TrainingProgram $trainingProgram): RedirectResponse
    {
        $before = $trainingProgram->only([
            'name',
            'code',
            'description',
            'total_program_fee',
            'downpayment_amount',
            'is_active',
        ]);

        $trainingProgram->update($this->validated($request, $trainingProgram));

        AdminActivityLog::record($request->user(), 'training_program.updated', $trainingProgram, [
            'before' => $before,
            'after' => $trainingProgram->fresh()->only(array_keys($before)),
        ]);

        return redirect()
            ->route('admin.schedules.index')
            ->with('saved', "Training program {$trainingProgram->name} updated.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?TrainingProgram $program = null): array
    {
        $request->merge([
            'program_name' => trim((string) $request->input('program_name')),
            'program_code' => Str::upper(trim((string) $request->input('program_code'))),
        ]);

        $validated = $request->validateWithBag('program', [
            'program_name' => ['required', 'string', 'max:120', 'not_regex:/[<>"\'`;{}|\\\\]/u'],
            'program_code' => [
                'required',
                'string',
                'max:50',
                'regex:/\A[A-Z0-9][A-Z0-9-]*\z/',
                Rule::unique('training_programs', 'code')->ignore($program?->id),
            ],
            'program_description' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>"\'`;{}|\\\\]/u'],
            'program_total_fee' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:1000000'],
            'program_downpayment' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:1000000'],
            'program_is_active' => ['nullable', 'boolean'],
        ], [
            'program_code.regex' => 'Use uppercase letters, numbers, and hyphens for the program code.',
            'program_code.unique' => 'This program code is already in use.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
        ]);

        if ((float) $validated['program_downpayment'] > (float) $validated['program_total_fee']) {
            throw ValidationException::withMessages([
                'program_downpayment' => 'The required downpayment cannot exceed the total program fee.',
            ])->errorBag('program');
        }

        return [
            'name' => $validated['program_name'],
            'code' => $validated['program_code'],
            'description' => $validated['program_description'] ?? null,
            'total_program_fee' => $validated['program_total_fee'],
            'downpayment_amount' => $validated['program_downpayment'],
            'is_active' => $request->boolean('program_is_active'),
        ];
    }
}
