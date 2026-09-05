<?php

namespace Database\Seeders;

use App\Models\InventoryKey;
use App\Models\Product;
use Illuminate\Database\Seeder;

class InventoryKeySeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()
            ->where('sku', 'KEY-CS2-PRIME')
            ->firstOrFail();

        for ($i = 1; $i <= 50; $i++) {
            InventoryKey::updateOrCreate(
                [
                    'code' => sprintf(
                        'CS2-TEST-KEY-%03d',
                        $i
                    ),
                ],
                [
                    'product_id' => $product->id,
                    'status' => 'available',
                    'order_id' => null,
                    'issued_at' => null,
                ]
            );
        }
    }
}
