<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->decimal('total_program_fee', 10, 2)->default(22000.00)->after('payment_method');
            $table->decimal('downpayment_amount', 10, 2)->default(2000.00)->after('total_program_fee');
            $table->decimal('total_paid_amount', 10, 2)->default(0.00)->after('downpayment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropColumn([
                'total_program_fee',
                'downpayment_amount',
                'total_paid_amount',
            ]);
        });
    }
};
