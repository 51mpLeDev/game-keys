<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'sku' => 'KEY-CS2-PRIME',
                'name' => 'CS2 Prime Status',
                'type' => 'key',
                'price' => 1290,
                'currency' => 'RUB',
                'image' => 'assets/img.png',
            ],
            [
                'sku' => 'KEY-GTA5',
                'name' => 'GTA V',
                'type' => 'key',
                'price' => 1990,
                'currency' => 'RUB',
                'image' => 'assets/img.png',
            ],
            [
                'sku' => 'KEY-DOOM-2016',
                'name' => 'DOOM 2016',
                'type' => 'key',
                'price' => 990,
                'currency' => 'RUB',
                'image' => 'assets/img.png',
            ],
            [
                'sku' => 'SUB-DISCORD-1M',
                'name' => 'Discord Nitro 1 месяц',
                'type' => 'subscription',
                'price' => 399,
                'currency' => 'RUB',
                'image' => 'assets/img.png',
            ],
            [
                'sku' => 'SUB-SPOTIFY-1M',
                'name' => 'Spotify Premium 1 месяц',
                'type' => 'subscription',
                'price' => 299,
                'currency' => 'RUB',
                'image' => 'assets/img.png',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
