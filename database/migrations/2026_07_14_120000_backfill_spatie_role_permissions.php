<?php

use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        RolePermissionMatrix::ensureConfigured();

        // Preserve every existing account by translating its current role.
        User::query()->select(['id', 'role'])->chunkById(100, function ($users): void {
            $users->each(fn (User $user) => RolePermissionMatrix::syncUser($user));
        });
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['roles'] ?? 'roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::query()->whereIn('name', RolePermissionMatrix::roleNames())->delete();
        Permission::query()->whereIn('name', RolePermissionMatrix::permissionNames())->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
