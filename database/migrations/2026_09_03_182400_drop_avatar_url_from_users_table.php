<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $users = DB::table('users')->get(['id', 'avatar_url', 'profile_photo_path']);

        foreach ($users as $user) {
            if (filled($user->profile_photo_path)) {
                continue;
            }

            $avatarUrl = trim((string) $user->avatar_url);
            if ($avatarUrl === '') {
                continue;
            }

            $path = parse_url($avatarUrl, PHP_URL_PATH) ?: $avatarUrl;
            $stored = str_starts_with((string) $path, '/storage/avatars/')
                ? ltrim(substr((string) $path, strlen('/storage/')), '/')
                : $avatarUrl;

            if ($stored === '' || str_contains($stored, '..')) {
                continue;
            }

            DB::table('users')->where('id', $user->id)->update([
                'profile_photo_path' => $stored,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_url')->nullable()->after('google_id');
        });

        $users = DB::table('users')->whereNotNull('profile_photo_path')->get(['id', 'profile_photo_path']);

        foreach ($users as $user) {
            $stored = (string) $user->profile_photo_path;
            $avatarUrl = str_starts_with($stored, 'avatars/')
                ? '/storage/'.$stored
                : $stored;

            DB::table('users')->where('id', $user->id)->update([
                'avatar_url' => $avatarUrl,
            ]);
        }
    }
};
