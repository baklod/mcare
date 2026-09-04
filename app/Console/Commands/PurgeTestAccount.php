<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProfilePhotoStore;
use Illuminate\Console\Command;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PurgeTestAccount extends Command
{
    protected $signature = 'mcare:purge-test-account
        {email : Exact Gmail address of the local test applicant or trainee}
        {--yes : Skip the interactive permanent-deletion confirmation}';

    protected $description = 'Permanently purge one local test account and its related enrollment artifacts';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Test-account purging is disabled outside local and testing environments.');

            return self::FAILURE;
        }

        $email = Str::lower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! Str::endsWith($email, '@gmail.com')) {
            $this->error('Provide one exact Gmail address. Partial addresses and wildcards are not allowed.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("No MCARE account exists for {$email}.");

            return self::FAILURE;
        }

        if (! in_array($user->role, ['applicant', 'trainee'], true)) {
            $this->error('Only local applicant or trainee test accounts can be purged with this command.');

            return self::FAILURE;
        }

        $application = $user->enrollmentApplication()->first();
        $queuedJobIds = $this->queuedNotificationJobIds($user);
        $localFiles = collect([
            $application?->birth_certificate_path,
            $application?->education_document_path,
            $application?->good_moral_certificate_path,
            $application?->id_photo_path,
            $application?->signature_path,
        ])->filter()->values();

        if ($application) {
            $localFiles = $localFiles
                ->merge($application->paymentTransactions()->pluck('receipt_proof_path')->filter())
                ->unique()
                ->values();
        }

        $officialFiles = $application
            ? $application->officialDocuments()
                ->whereNotNull('file_path')
                ->get(['storage_disk', 'file_path'])
            : collect();

        $this->warn("This permanently deletes {$email}, its enrollment/payment history, queued notifications, and uploaded test files.");
        if (! $this->option('yes') && ! $this->confirm('Continue with this exact account?', false)) {
            $this->info('No records or files were changed.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($user, $queuedJobIds): void {
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

            $user->syncRoles([]);
            $user->delete();
        });

        $deletedFiles = 0;
        foreach ($localFiles as $path) {
            $deletedFiles += Storage::disk('local')->delete($path) ? 1 : 0;
        }
        foreach ($officialFiles as $document) {
            $deletedFiles += Storage::disk($document->storage_disk ?: 'local')->delete($document->file_path) ? 1 : 0;
        }
        app(ProfilePhotoStore::class)->deleteFor($user);

        $this->info("Purged {$email}: account and related rows removed, {$deletedFiles} stored files deleted, ".count($queuedJobIds).' queued notification jobs removed.');

        return self::SUCCESS;
    }

    /** @return list<int> */
    private function queuedNotificationJobIds(User $user): array
    {
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
