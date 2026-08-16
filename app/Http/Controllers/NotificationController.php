<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user?->role, ['admin', 'trainer', 'trainee', 'alumni'], true)) {
            return redirect()->to(\App\Support\AccountPortal::urlFor($user));
        }

        return view('notifications.index', [
            'notifications' => $user->notifications()->latest()->paginate(20),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->ensureOwnedBy($request, $notification);
        $notification->markAsRead();

        return back()->with('saved', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        // The relationship scopes this update to the current signed-in account.
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('saved', 'All notifications marked as read.');
    }

    private function ensureOwnedBy(Request $request, DatabaseNotification $notification): void
    {
        abort_unless(
            $request->user()->notifications()->whereKey($notification->getKey())->exists(),
            404
        );
    }
}
