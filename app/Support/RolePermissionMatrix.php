<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionMatrix
{
    /**
     * Keep the legacy users.role values as the current source of truth while
     * Spatie permissions are introduced incrementally across the application.
     *
     * @return array<string, list<string>>
     */
    public static function roles(): array
    {
        return [
            'admin' => [
                'admin.access',
                'enrollments.review',
                'payments.verify',
                'schedules.manage',
                'trainees.manage',
                'modules.manage',
                'announcements.manage',
                'announcements.view',
                'quizzes.manage',
                'grades.view',
                'competencies.assess',
                'official-documents.manage',
                'accounts.manage',
                'reports.export',
                'logs.view',
                'alumni.jobs.manage',
                'comments.create',
                'comments.moderate',
            ],
            'trainer' => [
                'trainer.access',
                'trainees.view',
                'trainees.export',
                'modules.publish',
                'announcements.manage',
                'announcements.view',
                'quizzes.manage',
                'grades.view',
                'competencies.assess',
                'sessions.view',
                'comments.create',
                'comments.moderate',
            ],
            'trainee' => [
                'trainee.access',
                'modules.view',
                'announcements.view',
                'quizzes.take',
                'grades.view',
                'progress.update',
                'documents.view',
                'cotc.download',
                'payments.view',
                'alumni.jobs.view',
                'comments.create',
            ],
            'applicant' => [
                'enrollment.submit',
                'payments.view',
            ],
            // Compatibility only for records created before graduation became
            // a learning-status feature. Legacy alumni use the trainee layout.
            'alumni' => [
                'trainee.access',
                'documents.view',
                'cotc.download',
                'alumni.jobs.view',
            ],
        ];
    }

    /** @return list<string> */
    public static function roleNames(): array
    {
        return array_keys(self::roles());
    }

    /** @return list<string> */
    public static function permissionNames(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::roles()))));
    }

    public static function ensureConfigured(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissionNames() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (self::roles() as $roleName => $permissionNames) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($permissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function syncUser(User $user): void
    {
        if (! $user->exists) {
            return;
        }

        $roleName = (string) $user->role;

        // Unknown legacy values must never retain a previously privileged role.
        if (! array_key_exists($roleName, self::roles())) {
            $user->syncRoles([]);

            return;
        }

        if (! Role::query()->where('name', $roleName)->where('guard_name', 'web')->exists()) {
            self::ensureConfigured();
        }

        $user->syncRoles([$roleName]);
    }
}
