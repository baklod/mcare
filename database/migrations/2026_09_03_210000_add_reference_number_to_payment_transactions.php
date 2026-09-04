<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->string('reference_number', 120)->nullable()->after('ticket_number');
            $table->index('reference_number');
        });

        DB::table('payment_transactions')
            ->whereNull('reference_number')
            ->whereNotNull('ticket_number')
            ->update([
                'reference_number' => DB::raw('ticket_number'),
            ]);

        DB::table('payment_transactions')
            ->where('payment_channel', 'online')
            ->whereNull('reference_number')
            ->whereNotNull('or_number')
            ->update([
                'reference_number' => DB::raw('or_number'),
            ]);
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropIndex(['reference_number']);
            $table->dropColumn('reference_number');
        });
    }
};
