<?php

use App\Models\EnrollmentApplication;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('enrollment_number', 40)->nullable()->unique()->after('id');
        });

        EnrollmentApplication::query()
            ->whereNull('enrollment_number')
            ->orderBy('id')
            ->each(function (EnrollmentApplication $application): void {
                $application->forceFill([
                    'enrollment_number' => EnrollmentApplication::generateNumber(),
                ])->save();
            });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropUnique(['enrollment_number']);
            $table->dropColumn('enrollment_number');
        });
    }
};
