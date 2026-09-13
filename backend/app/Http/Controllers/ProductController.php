<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->input('max_price'));
        }

        $products = $query
            ->withCount([
                'inventoryKeys as stock' => fn ($query) => $query
                    ->where('status', 'available'),
            ])
            ->orderBy('id')
            ->limit(100)
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
