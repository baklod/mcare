<?php

use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = ['comments.create', 'comments.moderate'];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['roles'] ?? 'roles')
            || ! Schema::hasTable($tables['permissions'] ?? 'permissions')) {
            return;
        }

        RolePermissionMatrix::ensureConfigured();

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
            ->whereIn('name', self::PERMISSIONS)
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
