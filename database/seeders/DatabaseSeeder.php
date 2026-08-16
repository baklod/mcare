<?php

namespace Database\Seeders;

use App\Models\CareerOpportunity;
use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with standard MCARE portal accounts.
     */
    public function run(): void
    {
        RolePermissionMatrix::ensureConfigured();

        $accounts = [
            [
                'name' => 'MCARE Administrator',
                'email' => 'admin@mcare.com',
                'role' => 'admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'MCARE Trainer',
                'email' => 'trainer@mcare.com',
                'role' => 'trainer',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Approved Trainee',
                'email' => 'trainee@mcare.com',
                'role' => 'trainee',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'New Applicant',
                'email' => 'applicant@mcare.com',
                'role' => 'applicant',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $seededUsers = [];

        foreach ($accounts as $accountData) {
            $user = User::updateOrCreate(
                ['email' => $accountData['email']],
                $accountData
            );

            RolePermissionMatrix::syncUser($user);
            $seededUsers[$accountData['role']] = $user;
        }

        $alumni = User::updateOrCreate(
            ['email' => 'alumni@mcare.com'],
            [
                'name' => 'MCARE Alumni Demo',
                'role' => 'alumni',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        RolePermissionMatrix::syncUser($alumni);

        // Local demo opportunities keep the graduate portal reviewable before client data is available.
        foreach ([
            [
                'title' => 'Home Caregiver',
                'employer' => 'Mission Care Partner Residence',
                'location' => 'Iriga City',
                'employment_type' => CareerOpportunity::TYPE_FULL_TIME,
                'description' => 'Support daily living activities and provide respectful, person-centered home care.',
                'requirements' => 'Caregiving NC II, valid identification, and willingness to work rotating schedules.',
            ],
            [
                'title' => 'Care Support Associate',
                'employer' => 'Bicol Senior Wellness Center',
                'location' => 'Naga City',
                'employment_type' => CareerOpportunity::TYPE_PART_TIME,
                'description' => 'Assist the care team with mobility support, comfort routines, and client documentation.',
                'requirements' => 'Caregiving NC II graduate with current first-aid knowledge.',
            ],
        ] as $opportunity) {
            CareerOpportunity::updateOrCreate(
                [
                    'title' => $opportunity['title'],
                    'employer' => $opportunity['employer'],
                ],
                [
                    ...$opportunity,
                    'created_by_id' => $seededUsers['admin']->id,
                    'application_email' => 'careers@mcare.com',
                    'application_deadline' => now()->addDays(60),
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }
}
