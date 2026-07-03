<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->string('gender', 40);
            $table->string('contact_number', 30);
            $table->string('schedule_preference', 10);
            $table->string('street', 180);
            $table->string('barangay', 120);
            $table->string('city', 120);
            $table->string('province', 120);
            $table->string('zip_code', 20);
            $table->string('educational_attainment', 150);
            $table->string('school_name', 180);
            $table->unsignedSmallInteger('year_graduated');
            $table->string('status')->default('profile_submitted');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_applications');
    }
};
