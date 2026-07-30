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

        Schema::create('paymongo_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 160)->unique();
            $table->string('event_type', 120);
            $table->string('resource_id', 120)->nullable()->index();
            $table->boolean('livemode')->default(false);
            $table->char('payload_sha256', 64);
            $table->string('status', 30)->default('received');
            $table->string('error_code', 80)->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');
        Schema::dropIfExists('payment_attempts');
    }
};
