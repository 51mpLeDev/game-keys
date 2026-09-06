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
        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('promo_code_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('discount');

            $table->timestamps();

            $table->unique(['promo_code_id', 'order_id']);
            $table->index('promo_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
    }
};
