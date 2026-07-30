<?php

namespace Database\Seeders;

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

        foreach ($accounts as $accountData) {
            $user = User::updateOrCreate(
                ['email' => $accountData['email']],
                $accountData
            );

            RolePermissionMatrix::syncUser($user);
        }
    }
}
