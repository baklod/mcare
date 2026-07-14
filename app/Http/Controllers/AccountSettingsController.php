<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Support\AccountPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountSettingsController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.settings', $this->accountContext($request));
    }

    public function help(Request $request): View
    {
        $topics = match ($request->user()?->role) {
            'admin' => [
                ['Applications', 'Review applicant details, preview documents, and record missing requirements.'],
                ['Schedules and payments', 'Control enrollment windows, class schedules, and payment verification.'],
                ['Audit reports', 'Use Admin Logs to filter, print, or export system activity.'],
            ],
            'trainer' => [
                ['Teaching schedule', 'Your calendar follows the active batch schedule configured by the administrator.'],
                ['Learning materials', 'Publish PDFs, images, and videos to a batch or a specific trainee.'],
                ['Progress', 'Use trainee and report pages to review learner module activity.'],
            ],
            'trainee' => [
                ['Modules', 'Open assigned learning materials and mark lessons complete as you progress.'],
                ['Documents', 'Review admin feedback and replace enrollment documents that need correction.'],
                ['Schedule and payment', 'Your dashboard shows your assigned batch and current payment status.'],
            ],
            default => [
                ['Enrollment', 'Complete your profile, upload requirements, and monitor the application status.'],
            ],
        };

        return view('account.help', [
            ...$this->accountContext($request),
            'topics' => $topics,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()],
        ]);

        $request->user()->update(['password' => $validated['password']]);
        AdminActivityLog::record($request->user(), 'account.password.updated', $request->user(), [
            'role' => $request->user()?->role,
        ]);

        return back()->with('saved', 'Your password has been changed successfully.');
    }

    /**
     * Record a small allow-listed set of client-visible abuse signals.
     *
     * This is telemetry, not proof that a user acted maliciously: a modified
     * browser can omit it, so server-side authorization and rate limits remain
     * the real controls.
     */
    public function securityEvent(Request $request): Response
    {
        $validated = $request->validate([
            'event' => ['required', 'in:navigation_spam,rapid_action'],
        ]);

        AdminActivityLog::record($request->user(), 'account.security.client-event', $request->user(), [
            'event' => $validated['event'],
        ]);

        return response()->noContent();
    }

    private function accountContext(Request $request): array
    {
        $portalUrl = match ($request->user()?->role) {
            'admin' => route('admin.dashboard'),
            'trainer' => route('trainer.dashboard'),
            'trainee' => route('trainee.dashboard'),
            default => AccountPortal::urlFor($request->user()),
        };

        return [
            'user' => $request->user(),
            'roleLabel' => AccountPortal::roleLabelFor($request->user()),
            'portalUrl' => $portalUrl,
        ];
    }
}
