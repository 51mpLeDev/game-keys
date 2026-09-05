<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_issuances', function (Blueprint $table) {
            $table->id();

            $table->string('provider', 50);
            $table->string('request_id', 128);

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku', 100);

            $table->string('status', 30)->default('pending');

            $table->string('code', 255)->nullable();

            $table->text('error')->nullable();

            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            $table->unique(['provider', 'request_id']);

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_issuances');
    }
};
