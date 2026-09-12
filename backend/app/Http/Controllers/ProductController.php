<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->withCount([
                'inventoryKeys as stock' => fn ($query) => $query
                    ->where('status', 'available'),
            ])
            ->orderBy('id')
            ->get([
                'id',
                'sku',
                'name',
                'type',
                'price',
                'currency',
                'image',
            ]);

        return response()->json([
            'data' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'type' => $product->type,
                'price' => $product->price,
                'currency' => $product->currency,
                'image' => $product->image,
                'stock' => $product->stock,
            ])->values(),
        ]);
    }
}
