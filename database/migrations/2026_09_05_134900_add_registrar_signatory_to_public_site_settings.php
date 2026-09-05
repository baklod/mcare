<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_site_settings', function (Blueprint $table): void {
            $table->string('registrar_name', 180)->nullable()->after('youtube_url');
            $table->string('registrar_signature_type', 20)->nullable()->after('registrar_name');
            $table->string('registrar_signature_path')->nullable()->after('registrar_signature_type');
        });
    }

    public function down(): void
    {
        Schema::table('public_site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'registrar_name',
                'registrar_signature_type',
                'registrar_signature_path',
            ]);
        });
    }
};
