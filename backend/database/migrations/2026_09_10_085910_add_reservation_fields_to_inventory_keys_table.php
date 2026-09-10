<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_keys', function (Blueprint $table) {
            $table->timestamp('reserved_until')
                ->nullable()
                ->after('order_id');

            $table->index([
                'product_id',
                'status',
                'reserved_until',
                'id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_keys', function (Blueprint $table) {
            $table->dropIndex('inventory_keys_product_id_status_reserved_until_id_index');
            $table->dropColumn('reserved_until');
        });
    }
};
