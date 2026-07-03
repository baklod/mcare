<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('birth_certificate_path')->nullable()->after('signature_name');
            $table->string('education_document_path')->nullable()->after('birth_certificate_path');
            $table->string('good_moral_certificate_path')->nullable()->after('education_document_path');
            $table->string('id_photo_path')->nullable()->after('good_moral_certificate_path');
            $table->string('signature_type', 20)->nullable()->after('id_photo_path');
            $table->string('signature_path')->nullable()->after('signature_type');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'birth_certificate_path',
                'education_document_path',
                'good_moral_certificate_path',
                'id_photo_path',
                'signature_type',
                'signature_path',
            ]);
        });
    }
};
