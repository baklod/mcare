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
        $alumni->alumniProfile()->updateOrCreate([], [
            'is_available_for_duty' => true,
            'availability_updated_at' => now(),
        ]);

        // Demo records follow the client's privacy-minimal caregiving duty format.
        foreach ([
            [
                'estimated_start_date' => now()->addDays(14)->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 72,
                'specific_contraptions' => 'Walker',
                'condition_summary' => 'Needs mobility support during daily routines.',
            ],
            [
                'estimated_start_date' => now()->addDays(21)->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_MALE,
                'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
                'patient_age' => 80,
                'specific_contraptions' => 'Hospital bed',
                'condition_summary' => 'Requires assistance with repositioning and comfort routines.',
            ],
        ] as $opportunity) {
            $gender = CareerOpportunity::patientGenders()[$opportunity['patient_gender']];
            $mobility = CareerOpportunity::mobilityStatuses()[$opportunity['mobility_status']];

            CareerOpportunity::updateOrCreate(
                [
                    'title' => "Caregiving Duty - {$gender}, {$mobility}",
                    'employer' => 'MCARE-Coordinated Placement',
                ],
                [
                    ...$opportunity,
                    'created_by_id' => $seededUsers['admin']->id,
                    'description' => 'Privacy-minimal duty posting managed through the MCARE Alumni Hub.',
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }
    }
}
