<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();

            $table->string('event_id', 128)->unique();

            $table->string('order_public_id', 64)->index();

            $table->string('status', 20);

            $table->unsignedBigInteger('amount');
            $table->string('currency', 3);

            $table->timestamp('provider_created_at');

            $table->timestamp('processed_at')->nullable();

            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index([
                'order_public_id',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
