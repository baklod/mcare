<?php

use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const LMS_PERMISSIONS = [
        'announcements.manage',
        'announcements.view',
        'quizzes.manage',
        'quizzes.take',
        'grades.view',
    ];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['roles'] ?? 'roles')
            || ! Schema::hasTable($tables['permissions'] ?? 'permissions')) {
            return;
        }

        RolePermissionMatrix::ensureConfigured();

        // Re-sync existing users so their legacy role immediately receives LMS permissions.
        User::query()->select(['id', 'role'])->chunkById(100, function ($users): void {
            $users->each(fn (User $user) => RolePermissionMatrix::syncUser($user));
        });
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['permissions'] ?? 'permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::LMS_PERMISSIONS)
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
