<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_application_id')
                ->constrained('enrollment_applications')
                ->cascadeOnDelete();
            $table->string('provider', 30)->default('paymongo');
            $table->string('merchant_reference', 100)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->string('provider_checkout_id', 120)->nullable()->unique();
            $table->string('provider_payment_id', 120)->nullable()->unique();
            $table->string('provider_payment_intent_id', 120)->nullable()->unique();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('PHP');
            $table->string('status', 30)->default('creating');
            $table->text('checkout_url')->nullable();
            $table->boolean('livemode')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(
                ['enrollment_application_id', 'provider', 'status'],
                'payment_attempt_application_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
