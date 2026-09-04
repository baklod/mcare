<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\URL;

class StaffAccountCredentialsMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        $role = $this->user->role === 'trainer' ? 'trainer' : 'trainee';

        return new Envelope(
            subject: "Your MCARE {$role} account credentials",
        );
    }

    public function content(): Content
    {
        $roleLabel = $this->user->role === 'trainer' ? 'trainer' : 'trainee';

        return new Content(
            view: 'mail.staff-account-credentials',
            with: [
                'recipientName' => $this->user->name,
                'accountEmail' => $this->user->email,
                'plainPassword' => $this->plainPassword,
                'roleLabel' => $roleLabel,
                'loginUrl' => route('login'),
                'verificationUrl' => URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addMinutes((int) config('auth.verification.expire', 60)),
                    [
                        'id' => $this->user->getKey(),
                        'hash' => sha1($this->user->getEmailForVerification()),
                    ],
                ),
            ],
        );
    }
}
