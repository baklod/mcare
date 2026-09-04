<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdatedNotification;
use App\Notifications\PaymentVerifiedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPaymentTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_onsite_downpayment_and_credit_balance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        $response = $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
                'notes' => 'Received downpayment at registration desk.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction = PaymentTransaction::query()->where('enrollment_application_id', $application->id)->firstOrFail();
        $this->assertStringStartsWith('MCARE-OR-', (string) $transaction->or_number);

        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'amount' => 2000.00,
            'or_number' => $transaction->or_number,
            'transaction_type' => 'downpayment',
            'status' => 'verified',
        ]);

        $application->refresh();
        $this->assertEquals(2000.00, (float) $application->total_paid_amount);
        $this->assertEquals(20000.00, $application->remainingBalance());
        $this->assertEquals('partially_paid', $application->payment_status);
        $this->assertTrue($application->isDownpaymentSatisfied());
        $this->assertSame($transaction->or_number, $application->payment_receipt_number);

        $adminNotice = 'On-site payment of ₱2,000.00 recorded for Juan Dela Cruz (OR #'.$transaction->or_number.').';
        $paymentPage = $this->actingAs($admin)->get(route('admin.payment-schedules.index'))->assertOk();
        $this->assertSame(1, substr_count($paymentPage->getContent(), $adminNotice));
        $paymentPage
            ->assertSee('data-lookup-input', false)
            ->assertSee('Reference number', false)
            ->assertDontSee('id="record-or-section"', false)
            ->assertDontSee('id="record-enrollee-select"', false);
    }

    public function test_pay_on_site_and_paymongo_store_reference_numbers_on_the_admin_ledger(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $onsiteTrainee = User::factory()->create(['role' => 'trainee']);
        $onsiteApplication = $this->createApprovedApplication($onsiteTrainee, $this->batch());
        $onsiteApplication->forceFill([
            'payment_method' => null,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'payment_reference' => null,
            'payment_receipt_number' => null,
        ])->save();

        $this->actingAs($onsiteTrainee)
            ->post(route('payment.select'), ['payment_method' => 'onsite'])
            ->assertRedirect(route('payment.receipt'));

        $onsiteApplication->refresh();
        $this->assertNotNull($onsiteApplication->payment_reference);
        $this->assertStringStartsWith('MCARE-SITE-', $onsiteApplication->payment_reference);
        $this->assertNotNull($onsiteApplication->payment_receipt_number);
        $this->assertStringStartsWith('MCARE-OR-', $onsiteApplication->payment_receipt_number);
        $this->assertDatabaseHas('payment_transactions', [
            'enrollment_application_id' => $onsiteApplication->id,
            'reference_number' => $onsiteApplication->payment_reference,
            'ticket_number' => $onsiteApplication->payment_reference,
            'or_number' => $onsiteApplication->payment_receipt_number,
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-schedules.index'))
            ->assertOk()
            ->assertSee($onsiteApplication->payment_reference)
            ->assertSee('On-site reference', false);
    }

    public function test_admin_can_look_up_enrollee_from_official_receipt_ticket_or_enrollment_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $ticket = PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 2000,
            'ticket_number' => 'MCARE-OT-LOOKUP1',
            'or_number' => 'OR-LOOKUP-PENDING',
            'status' => PaymentTransaction::STATUS_PENDING,
            'paid_at' => now(),
        ]);
        $application->forceFill([
            'enrollment_number' => 'MCE-2026-LOOKUP',
            'payment_receipt_number' => 'MCR-260903-LOOKUP',
        ])->save();

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'OR-LOOKUP-PENDING']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('name', 'Dela Cruz, Juan')
            ->assertJsonPath('email', $trainee->email)
            ->assertJsonPath('matched_by', 'or_number')
            ->assertJsonPath('reuse_or_number', true)
            ->assertJsonPath('or_number', 'OR-LOOKUP-PENDING');

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'MCARE-OT-LOOKUP1']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('matched_by', 'ticket')
            ->assertJsonPath('pending_ticket', $ticket->ticket_number);

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'mce-2026-lookup']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('matched_by', 'enrollment');

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'OR-DOES-NOT-EXIST']))
            ->assertOk()
            ->assertJsonPath('found', false);
    }

    public function test_admin_can_look_up_enrollee_from_spaced_onsite_reference(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $application->forceFill([
            'payment_reference' => 'MCARE-SITE-260903-KAGKTK1H',
            'downpayment_amount' => 3500.00,
        ])->save();

        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 3500,
            'ticket_number' => 'MCARE-SITE-260903-KAGKTK1H',
            'reference_number' => 'MCARE-SITE-260903-KAGKTK1H',
            'status' => PaymentTransaction::STATUS_PENDING,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'MCARE SITE 260903 KAGKTK1H']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('name', 'Dela Cruz, Juan')
            ->assertJsonPath('email', $trainee->email)
            ->assertJsonPath('matched_by', 'ticket');

        $this->assertEquals(3500.0, (float) $response->json('downpayment_amount'));
        $this->assertEquals(3500.0, (float) $response->json('suggested_amount'));
    }

    public function test_admin_can_look_up_enrollee_from_reference_suffix(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $application->forceFill([
            'payment_reference' => 'MCARE-SITE-260903-KAGKTK1H',
        ])->save();

        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 2000,
            'ticket_number' => 'MCARE-SITE-260903-KAGKTK1H',
            'reference_number' => 'MCARE-SITE-260903-KAGKTK1H',
            'status' => PaymentTransaction::STATUS_PENDING,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'KAGKTK1H']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('name', 'Dela Cruz, Juan')
            ->assertJsonPath('matched_by', 'ticket');
    }

    public function test_admin_can_look_up_enrollee_from_paymongo_reference(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $application->forceFill([
            'payment_method' => 'online',
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_reference' => 'MCARE-ONLINE-260902-MXSCCNUG',
            'payment_receipt_number' => 'pay_eXkzQnLXqVo874mxWDKwW1sY',
            'total_paid_amount' => 2000,
        ])->save();

        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONLINE,
            'amount' => 2000,
            'reference_number' => 'pay_eXkzQnLXqVo874mxWDKwW1sY',
            'or_number' => 'pay_eXkzQnLXqVo874mxWDKwW1sY',
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.payment-schedules.lookup', ['q' => 'pay_eXkzQnLXqVo874mxWDKwW1sY']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('application_id', $application->id)
            ->assertJsonPath('matched_by', 'or_number');
    }

    public function test_record_onsite_modal_does_not_hardcode_downpayment_amounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.payment-schedules.index'))
            ->assertOk()
            ->assertSee('id="record-lookup-button"', false)
            ->assertSee('id="record-enrollee-name"', false)
            ->assertSee('id="record-lookup-query"', false)
            ->assertSee('record-onsite-layout', false)
            ->assertSee('Reference number', false)
            ->assertDontSee('id="record-or-section"', false)
            ->assertSee('>Downpayment</option>', false)
            ->assertDontSee('Downpayment (Initial', false)
            ->assertDontSee('₱2,000 Downpayment', false)
            ->assertDontSee('data-preset-amount="2000.00"', false)
            ->assertDontSee('data-preset-amount="5000.00"', false)
            ->assertDontSee('value="2000.00"', false);
    }

    public function test_recording_payment_against_a_pending_ticket_verifies_that_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $ticket = PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 2000,
            'ticket_number' => 'MCARE-OT-VERIFY1',
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);

        $application->forceFill([
            'payment_reference' => 'MCARE-SITE-260903-EMAILTEST',
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseCount('payment_transactions', 1);
        $ticket->refresh();
        $this->assertSame(PaymentTransaction::STATUS_VERIFIED, $ticket->status);
        $this->assertStringStartsWith('MCARE-OR-', (string) $ticket->or_number);
        $this->assertSame('MCARE-SITE-260903-EMAILTEST', $ticket->reference_number);
        $this->assertEquals(2000.00, (float) $application->refresh()->total_paid_amount);
    }

    public function test_onsite_payment_receipt_email_shows_reference_and_generated_or(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());
        $application->forceFill([
            'payment_reference' => 'MCARE-SITE-260903-EMAILTEST',
            'payment_method' => 'onsite',
        ])->save();

        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $trainee->id,
            'transaction_type' => PaymentTransaction::TYPE_DOWNPAYMENT,
            'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
            'amount' => 2000,
            'ticket_number' => 'MCARE-SITE-260903-EMAILTEST',
            'reference_number' => 'MCARE-SITE-260903-EMAILTEST',
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        Mail::assertSent(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($trainee): bool {
            $html = $mail->render();

            return $mail->hasTo($trainee->email)
                && str_contains($html, 'Official payment receipt')
                && str_contains($html, 'Reference number')
                && str_contains($html, 'Official Receipt (OR) #')
                && str_contains($html, 'MCARE-SITE-260903-EMAILTEST')
                && str_contains($html, 'MCARE-OR-')
                && str_contains($html, 'On-site');
        });
    }

    public function test_admin_recording_full_tuition_sets_status_to_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        // Record initial downpayment
        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
            ]);

        // Record remaining balance
        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 20000.00,
                'transaction_type' => 'balance_settlement',
                'paid_at' => now()->toDateString(),
                'notes' => 'Full balance completed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertEquals(22000.00, (float) $application->total_paid_amount);
        $this->assertEquals(0.00, $application->remainingBalance());
        $this->assertEquals('paid', $application->payment_status);
    }

    public function test_verified_downpayment_moves_applicant_to_account_review_then_approval_unlocks_login(): void
    {
        Notification::fake();
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'email' => 'payment.lifecycle@gmail.com',
            'email_verified_at' => now(),
            'password' => 'Password123',
        ]);
        $application = $this->createApprovedApplication($applicant, $this->batch());
        $application->forceFill([
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'payment_reference' => 'MCARE-SITE-260903-LIFECYCLE',
            'payment_method' => 'onsite',
        ])->save();

        $this->actingAs($admin)
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 2000.00,
                'transaction_type' => 'downpayment',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction = PaymentTransaction::query()->where('enrollment_application_id', $application->id)->firstOrFail();

        $application->refresh();
        $this->assertSame(EnrollmentApplication::PAYMENT_PARTIALLY_PAID, $application->payment_status);
        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $application->status);
        $this->assertTrue($application->hasEnrollmentPaymentClearance());
        $this->assertNotNull(data_get($application->payment_meta, 'enrollment_clearance_notified_at'));
        $this->assertSame(
            EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            $applicant->refresh()->applicant_status,
        );

        Notification::assertSentTo(
            $applicant,
            PaymentVerifiedNotification::class,
            fn (PaymentVerifiedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true),
        );

        Mail::assertSent(PaymentReceiptMail::class, function (PaymentReceiptMail $mail) use ($applicant, $application, $transaction): bool {
            $html = $mail->render();

            return $mail->hasTo($applicant->email)
                && $mail->application->is($application)
                && str_contains($html, $transaction->or_number)
                && str_contains($html, 'MCARE-SITE-260903-LIFECYCLE')
                && str_contains($html, 'Official payment receipt');
        });

        Auth::logout();
        $session = ['enrollment.payment_application_id' => $application->id];
        $this->withSession($session)
            ->getJson(route('payment.status'))
            ->assertOk()
            ->assertJson([
                'payment_verified' => true,
                'account_approved' => false,
                'completion_url' => route('payment.complete'),
            ]);

        $this->actingAs($applicant)
            ->withSession($session)
            ->get(route('payment.complete'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('payment_verified');
        $this->assertGuest();

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Payment verified successfully')
            ->assertSee('Please wait while the administrator completes your account verification');

        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'Password123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Payment and documents verified.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $this->assertSame('trainee', $applicant->refresh()->role);
        Notification::assertSentTo(
            $applicant,
            EnrollmentStatusUpdatedNotification::class,
            fn (EnrollmentStatusUpdatedNotification $notification): bool => $notification->application->status === EnrollmentApplication::STATUS_APPROVED
                && $notification->queue === 'mail'
                && $notification->toMail($applicant)->subject === 'Your MCARE account is approved - you can now log in',
        );

        Auth::logout();
        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'Password123',
        ])->assertRedirect(route('trainee.dashboard'));
        $this->assertAuthenticatedAs($applicant);
    }

    public function test_paid_applicant_denial_is_terminal_and_explains_the_next_steps(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'email_verified_at' => now(),
            'password' => 'Password123',
        ]);
        $application = $this->createApprovedApplication($applicant, $this->batch());
        $application->forceFill([
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_verified_at' => now(),
            'review_released_at' => now(),
        ])->save();

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_DENIED,
                'admin_notes' => 'Please contact MCARE regarding the submitted requirement.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $session = ['enrollment.payment_application_id' => $application->id];
        Auth::logout();

        $this->withSession($session)
            ->getJson(route('payment.status'))
            ->assertOk()
            ->assertJson([
                'payment_verified' => true,
                'application_status' => EnrollmentApplication::STATUS_DENIED,
                'account_approved' => false,
                'account_denied' => true,
            ]);

        $this->withSession($session)
            ->get(route('payment.complete'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('account_denied');

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Enrollment application not approved')
            ->assertSee('Your verified payment remains recorded')
            ->assertSee('Please contact MCARE regarding the submitted requirement.')
            ->assertDontSee('Please wait while the administrator completes your account verification');

        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'Password123',
        ])->assertRedirect(route('enrollment.create'))
            ->assertSessionHas('reapply_notice');
        $this->assertAuthenticatedAs($applicant);

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Resubmit corrected enrollment')
            ->assertSee('Please contact MCARE regarding the submitted requirement.');

        Notification::assertSentTo(
            $applicant,
            EnrollmentStatusUpdatedNotification::class,
            fn (EnrollmentStatusUpdatedNotification $notification): bool => $notification->application->status === EnrollmentApplication::STATUS_DENIED
                && $notification->toMail($applicant)->subject === 'Important: Your MCARE enrollment application was not approved',
        );
    }

    public function test_trainee_can_upload_payment_proof_and_admin_can_verify(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $application = $this->createApprovedApplication($trainee, $batch);

        $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), [
                'amount' => 5000.00,
                'or_number' => 'OR-ONLINE-999',
                'transaction_type' => 'installment',
                'paid_at' => now()->toDateString(),
                'receipt_proof' => $file,
                'notes' => 'Paid via cashier bank deposit.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction = PaymentTransaction::query()
            ->where('enrollment_application_id', $application->id)
            ->firstOrFail();

        $this->assertEquals('pending_verification', $transaction->status);
        $this->assertEquals('OR-ONLINE-999', $transaction->or_number);
        $this->assertNotNull($transaction->receipt_proof_path);

        $this->actingAs($admin)
            ->get(route('admin.payment-schedules.transactions.proof', $transaction))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/pdf');

        // Admin verifies the transaction
        $this->actingAs($admin)
            ->patch(route('admin.payment-schedules.transactions.verify', $transaction), [
                'action' => 'verify',
                'notes' => 'Validated bank slip against cashier bank feed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $transaction->refresh();
        $this->assertEquals('verified', $transaction->status);

        $application->refresh();
        $this->assertEquals(5000.00, (float) $application->total_paid_amount);
        $this->assertEquals(17000.00, $application->remainingBalance());
    }

    public function test_admin_and_trainee_cannot_submit_more_than_the_remaining_balance(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());

        $this->actingAs($admin)
            ->from(route('admin.payment-schedules.index'))
            ->post(route('admin.payment-schedules.transactions.store', $application), [
                'amount' => 22000.01,
                'or_number' => 'OR-OVERPAY-ADMIN',
                'transaction_type' => 'full_payment',
                'paid_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.payment-schedules.index'))
            ->assertSessionHasErrors('amount');

        $this->actingAs($trainee)
            ->from(route('trainee.payments'))
            ->post(route('trainee.payments.upload-proof'), [
                'amount' => 22000.01,
                'or_number' => 'OR-OVERPAY-TRAINEE',
                'transaction_type' => 'full_payment',
                'paid_at' => now()->toDateString(),
                'receipt_proof' => UploadedFile::fake()->create('overpay.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('trainee.payments'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payment_transactions', 0);
        $this->assertSame([], Storage::disk('local')->allFiles("payment-receipts/{$application->id}"));
    }

    public function test_receipt_proof_is_required_and_or_number_cannot_be_reused(): void
    {
        Storage::fake('local');

        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->createApprovedApplication($trainee, $this->batch());

        $payload = [
            'amount' => 2000,
            'or_number' => 'OR-UNIQUE-001',
            'transaction_type' => 'downpayment',
            'paid_at' => now()->toDateString(),
        ];

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), $payload)
            ->assertSessionHasErrors('receipt_proof');

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), [
                ...$payload,
                'receipt_proof' => UploadedFile::fake()->create('receipt-one.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($trainee)
            ->post(route('trainee.payments.upload-proof'), [
                ...$payload,
                'receipt_proof' => UploadedFile::fake()->create('receipt-two.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('or_number');

        $this->assertDatabaseCount('payment_transactions', 1);
    }

    private function batch(): TrainingBatch
    {
        return TrainingBatch::create([
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
    }

    private function createApprovedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
        return EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1998-05-12',
            'gender' => 'Male',
            'contact_number' => '09180000000',
            'schedule_preference' => 'AM',
            'street' => 'San Jose St.',
            'barangay' => 'Poblacion',
            'city' => 'Naga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4400',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'University of Nueva Caceres',
            'year_graduated' => 2022,
            'birth_certificate_path' => 'enrollment-documents/test/birth-certificate.pdf',
            'education_document_path' => 'enrollment-documents/test/education-document.pdf',
            'good_moral_certificate_path' => 'enrollment-documents/test/good-moral-certificate.pdf',
            'id_photo_path' => 'enrollment-documents/test/id-photo.jpg',
            'signature_path' => 'enrollment-documents/test/signature.png',
            'document_review' => [
                'birth-certificate' => ['status' => 'accepted', 'note' => null],
                'education-document' => ['status' => 'accepted', 'note' => null],
                'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
                'id-photo' => ['status' => 'accepted', 'note' => null],
                'signature' => ['status' => 'accepted', 'note' => null],
            ],
            'documents_reviewed_at' => now(),
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'review_released_at' => now(),
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_method' => 'onsite',
        ]);
    }
}
