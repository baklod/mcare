<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_name');
            $table->string('extension_name', 30)->nullable()->after('last_name');
            $table->string('contact_email')->nullable()->after('email');
            $table->string('contact_number', 30)->nullable()->after('contact_email');
            $table->date('birth_date')->nullable()->after('contact_number');
            $table->string('birthplace_city', 120)->nullable()->after('birth_date');
            $table->string('birthplace_province', 120)->nullable()->after('birthplace_city');
            $table->string('birthplace_region', 120)->nullable()->after('birthplace_province');
            $table->string('gender', 40)->nullable()->after('birthplace_region');
            $table->string('civil_status', 50)->nullable()->after('gender');
            $table->string('employment_status', 80)->nullable()->after('civil_status');
            $table->string('employment_type', 80)->nullable()->after('employment_status');
            $table->string('nationality', 80)->nullable()->after('employment_type');
            $table->string('street', 180)->nullable()->after('nationality');
            $table->string('barangay', 120)->nullable()->after('street');
            $table->string('city', 120)->nullable()->after('barangay');
            $table->string('province', 120)->nullable()->after('city');
            $table->string('region', 120)->nullable()->after('province');
            $table->string('zip_code', 20)->nullable()->after('region');
            $table->string('educational_attainment', 150)->nullable()->after('zip_code');
            $table->string('school_name', 180)->nullable()->after('educational_attainment');
            $table->unsignedSmallInteger('year_graduated')->nullable()->after('school_name');
            $table->string('guardian_name', 180)->nullable()->after('year_graduated');
            $table->string('guardian_address', 255)->nullable()->after('guardian_name');
        });

        $applications = DB::table('enrollment_applications')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get();

        foreach ($applications as $application) {
            $name = trim(implode(' ', array_filter([
                $application->first_name ?? null,
                $application->middle_name ?? null,
                $application->last_name ?? null,
                $application->extension_name ?? null,
            ])));

            $payload = [
                'first_name' => $application->first_name,
                'middle_name' => $application->middle_name,
                'last_name' => $application->last_name,
                'extension_name' => $application->extension_name ?? null,
                'contact_email' => $application->email,
                'contact_number' => $application->contact_number,
                'birth_date' => $application->birth_date,
                'birthplace_city' => $application->birthplace_city ?? null,
                'birthplace_province' => $application->birthplace_province ?? null,
                'birthplace_region' => $application->birthplace_region ?? null,
                'gender' => $application->gender,
                'civil_status' => $application->civil_status ?? null,
                'employment_status' => $application->employment_status ?? null,
                'employment_type' => $application->employment_type ?? null,
                'nationality' => $application->nationality ?? null,
                'street' => $application->street,
                'barangay' => $application->barangay,
                'city' => $application->city,
                'province' => $application->province,
                'region' => $application->region ?? null,
                'zip_code' => $application->zip_code,
                'educational_attainment' => $application->educational_attainment,
                'school_name' => $application->school_name,
                'year_graduated' => $application->year_graduated,
                'guardian_name' => $application->guardian_name ?? null,
                'guardian_address' => $application->guardian_address ?? null,
            ];

            if ($name !== '') {
                $payload['name'] = $name;
            }

            DB::table('users')->where('id', $application->user_id)->update($payload);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'middle_name',
                'last_name',
                'extension_name',
                'contact_email',
                'contact_number',
                'birth_date',
                'birthplace_city',
                'birthplace_province',
                'birthplace_region',
                'gender',
                'civil_status',
                'employment_status',
                'employment_type',
                'nationality',
                'street',
                'barangay',
                'city',
                'province',
                'region',
                'zip_code',
                'educational_attainment',
                'school_name',
                'year_graduated',
                'guardian_name',
                'guardian_address',
            ]);
        });
    }
};
