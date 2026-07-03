<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('extension_name', 30)->nullable()->after('last_name');
            $table->string('program', 120)->default('Caregiving NC II')->after('email');
            $table->string('nationality', 80)->nullable()->after('contact_number');
            $table->string('civil_status', 50)->nullable()->after('gender');
            $table->string('employment_status', 80)->nullable()->after('civil_status');
            $table->string('employment_type', 80)->nullable()->after('employment_status');
            $table->string('birthplace_city', 120)->nullable()->after('birth_date');
            $table->string('birthplace_province', 120)->nullable()->after('birthplace_city');
            $table->string('birthplace_region', 120)->nullable()->after('birthplace_province');
            $table->string('region', 120)->nullable()->after('province');
            $table->string('guardian_name', 180)->nullable()->after('year_graduated');
            $table->string('guardian_address', 255)->nullable()->after('guardian_name');
            $table->string('classification', 120)->nullable()->after('guardian_address');
            $table->string('disability_type', 120)->nullable()->after('classification');
            $table->string('disability_cause', 120)->nullable()->after('disability_type');
            $table->string('scholarship_type', 120)->nullable()->after('disability_cause');
            $table->boolean('privacy_consent')->default(false)->after('scholarship_type');
            $table->string('signature_name', 180)->nullable()->after('privacy_consent');
            $table->date('date_accomplished')->nullable()->after('signature_name');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'extension_name',
                'program',
                'nationality',
                'civil_status',
                'employment_status',
                'employment_type',
                'birthplace_city',
                'birthplace_province',
                'birthplace_region',
                'region',
                'guardian_name',
                'guardian_address',
                'classification',
                'disability_type',
                'disability_cause',
                'scholarship_type',
                'privacy_consent',
                'signature_name',
                'date_accomplished',
            ]);
        });
    }
};
