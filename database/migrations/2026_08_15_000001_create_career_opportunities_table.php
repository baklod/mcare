<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 160);
            $table->string('employer', 160);
            $table->string('location', 160)->nullable();
            $table->string('employment_type', 40)->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('application_url', 2048)->nullable();
            $table->string('application_email')->nullable();
            $table->dateTime('application_deadline')->nullable();
            $table->boolean('is_published')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'application_deadline']);
            $table->index(['employer', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_opportunities');
    }
};
