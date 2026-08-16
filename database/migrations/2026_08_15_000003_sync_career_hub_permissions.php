<?php

use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Keep already-created accounts aligned with the expanded career hub permissions.
        RolePermissionMatrix::ensureConfigured();

        User::query()->select(['id', 'role'])->chunkById(100, function ($users): void {
            $users->each(fn (User $user) => RolePermissionMatrix::syncUser($user));
        });
    }

    public function down(): void
    {
        // The permission matrix remains the source of truth for future syncs.
    }
};
