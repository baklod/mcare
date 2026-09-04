<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_updates', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->string('description', 500)->nullable();
            $table->string('facebook_url', 500);
            $table->unsignedTinyInteger('position')->default(1);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_updates');
    }
};
