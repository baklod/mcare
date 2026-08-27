<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcement_deliveries')) {
            Schema::create('announcement_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('announcement_type', 40);
                $table->unsignedBigInteger('announcement_id');
                $table->string('delivery_reason', 40)->default('publication');
                $table->timestamps();

                $table->unique(
                    ['user_id', 'announcement_type', 'announcement_id'],
                    'announcement_deliveries_user_source_unique',
                );
                $table->index(
                    ['announcement_type', 'announcement_id'],
                    'announcement_deliveries_source_index',
                );
            });
        }

        $this->backfillExistingNotificationDeliveries();
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_deliveries');
    }

    private function backfillExistingNotificationDeliveries(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $notificationTypes = [
            'App\\Notifications\\LmsAnnouncementPublished' => 'trainer',
            'App\\Notifications\\AdminAnnouncementNotification' => 'admin',
        ];

        DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereIn('type', array_keys($notificationTypes))
            ->select(['notifiable_id', 'type', 'data', 'created_at', 'updated_at'])
            ->orderBy('created_at')
            ->get()
            ->each(function (object $notification) use ($notificationTypes): void {
                $data = json_decode((string) $notification->data, true);
                $announcementId = is_array($data) ? (int) ($data['announcement_id'] ?? 0) : 0;

                if ($announcementId <= 0) {
                    return;
                }

                DB::table('announcement_deliveries')->insertOrIgnore([
                    'user_id' => $notification->notifiable_id,
                    'announcement_type' => $notificationTypes[$notification->type],
                    'announcement_id' => $announcementId,
                    'delivery_reason' => 'existing_notification',
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                ]);
            });
    }
};
