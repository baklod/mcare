<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('avatar_url');
        });

        $users = DB::table('users')
            ->whereNotNull('avatar_url')
            ->get(['id', 'avatar_url']);

        foreach ($users as $user) {
            $path = parse_url((string) $user->avatar_url, PHP_URL_PATH) ?: (string) $user->avatar_url;
            $prefix = '/storage/avatars/';

            if (! str_starts_with($path, $prefix)) {
                continue;
            }

            $relative = ltrim(substr($path, strlen('/storage/')), '/');
            if ($relative === '' || str_contains($relative, '..')) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'profile_photo_path' => $relative,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });
    }
};
