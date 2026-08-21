<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['roles'] ?? 'roles')
            || ! Schema::hasTable($tables['permissions'] ?? 'permissions')
            || ! Schema::hasTable($tables['role_has_permissions'] ?? 'role_has_permissions')) {
            return;
        }

        $permissionId = DB::table($tables['permissions'])
            ->where('name', 'alumni.jobs.view')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table($tables['roles'])
            ->whereIn('name', ['trainee', 'alumni'])
            ->where('guard_name', 'web')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table($tables['role_has_permissions'])->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        if (! Schema::hasTable($tables['roles'] ?? 'roles')
            || ! Schema::hasTable($tables['permissions'] ?? 'permissions')
            || ! Schema::hasTable($tables['role_has_permissions'] ?? 'role_has_permissions')) {
            return;
        }

        $permissionId = DB::table($tables['permissions'])
            ->where('name', 'alumni.jobs.view')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table($tables['roles'])
            ->whereIn('name', ['trainee', 'alumni'])
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table($tables['role_has_permissions'])
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', $roleIds)
            ->delete();
    }
};
