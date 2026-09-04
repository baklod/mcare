<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');
    }

    public function down(): void
    {
        // Webhook events are no longer stored. Recreate nothing on rollback.
    }
};
