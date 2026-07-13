<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->json('document_review')->nullable()->after('signature_path');
            $table->timestamp('documents_reviewed_at')->nullable()->after('document_review');
            $table->foreignId('documents_reviewed_by_id')
                ->nullable()
                ->after('documents_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documents_reviewed_by_id');
            $table->dropColumn(['document_review', 'documents_reviewed_at']);
        });
    }
};
