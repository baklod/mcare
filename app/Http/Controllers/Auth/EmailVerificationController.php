<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            AdminActivityLog::record($user, 'account.email.verified', $user);
        }

        $historicalClaim = $user->historicalAlumniClaim()->first();
        if ($historicalClaim?->status === HistoricalAlumniClaim::STATUS_PENDING_EMAIL) {
            $historicalClaim->forceFill([
                'status' => HistoricalAlumniClaim::STATUS_PENDING_ONSITE,
            ])->save();

            AdminActivityLog::record($user, 'historical-alumni.email.verified', $historicalClaim);
        }

        return redirect()
            ->route('login')
            ->with('verified', $historicalClaim
                ? 'Email verified. Your alumni claim is waiting for on-site identity and training-record verification.'
                : 'Email verified. You can now sign in with your MCARE account.');
    }
}
