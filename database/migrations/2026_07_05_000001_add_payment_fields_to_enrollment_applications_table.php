<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('status');
            $table->string('payment_status', 40)->default('not_selected')->after('payment_method');
            $table->decimal('payment_amount', 10, 2)->default(2000.00)->after('payment_status');
            $table->string('payment_currency', 3)->default('PHP')->after('payment_amount');
            $table->string('payment_reference', 80)->nullable()->unique()->after('payment_currency');
            $table->string('payment_receipt_number', 80)->nullable()->unique()->after('payment_reference');
            $table->timestamp('payment_receipt_expires_at')->nullable()->after('payment_receipt_number');
            $table->timestamp('payment_selected_at')->nullable()->after('payment_receipt_expires_at');
            $table->string('paymongo_checkout_reference', 100)->nullable()->after('payment_selected_at');
            $table->string('paymongo_checkout_url')->nullable()->after('paymongo_checkout_reference');
            $table->json('payment_meta')->nullable()->after('paymongo_checkout_url');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applications', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropUnique(['payment_receipt_number']);
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_amount',
                'payment_currency',
                'payment_reference',
                'payment_receipt_number',
                'payment_receipt_expires_at',
                'payment_selected_at',
                'paymongo_checkout_reference',
                'paymongo_checkout_url',
                'payment_meta',
            ]);
        });
    }
};
