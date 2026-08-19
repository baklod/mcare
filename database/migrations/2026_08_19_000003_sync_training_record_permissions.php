<?php

use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        RolePermissionMatrix::ensureConfigured();
        User::query()->eachById(fn (User $user) => RolePermissionMatrix::syncUser($user));
    }

    public function down(): void
    {
        // The permission matrix remains the source of truth for future syncs.
    }
};
