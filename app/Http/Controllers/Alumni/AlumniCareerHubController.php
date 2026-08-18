<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CareerOpportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniCareerHubController extends Controller
{
    public function index(Request $request): View
    {
        $profile = $request->user()->alumniProfile()->firstOrCreate([], [
            'is_available_for_duty' => false,
        ]);

        return view('alumni.dashboard', [
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->orderBy('estimated_start_date')
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
            'alumniProfile' => $profile,
        ]);
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_available_for_duty' => ['required', 'boolean'],
        ]);

        $available = (bool) $validated['is_available_for_duty'];
        $profile = $request->user()->alumniProfile()->updateOrCreate([], [
            'is_available_for_duty' => $available,
            'availability_updated_at' => now(),
        ]);

        AdminActivityLog::record($request->user(), 'alumni.availability.updated', $profile, [
            'is_available_for_duty' => $available,
        ]);

        // Send one status-specific flash payload to the shared alumni layout.
        return back()->with([
            'saved' => $available
                ? 'You are now marked Available for Duty.'
                : 'Your availability is now set to unavailable.',
            'saved_icon' => $available ? 'circle-check' : 'circle-minus',
            'saved_tone' => $available ? 'available' : 'unavailable',
        ]);
    }
}
