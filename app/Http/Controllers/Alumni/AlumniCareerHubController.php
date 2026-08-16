<?php

namespace App\Http\Controllers\Alumni;

use App\Http\Controllers\Controller;
use App\Models\CareerOpportunity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniCareerHubController extends Controller
{
    public function index(Request $request): View
    {
        return view('alumni.dashboard', [
            'jobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->latest('published_at')
                ->paginate(12),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
