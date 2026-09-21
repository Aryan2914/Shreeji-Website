<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * List user's wishlist.
     * GET /api/v1/wishlist
     */
    public function index(Request $request): JsonResponse
    {
        $wishlisted = $request->user()
            ->wishlists()
            ->with([
                'product' => fn ($q) => $q->active()->with([
                    'brand:id,name,slug',
                    'images' => fn ($q2) => $q2->orderBy('sort_order')->limit(1),
                    'skus' => fn ($q2) => $q2->active()->select('id', 'product_id', 'selling_price', 'retail_price', 'stock_quantity', 'reserved_quantity'),
                ]),
            ])
            ->latest()
            ->get()
            ->pluck('product')
            ->filter(); // Remove null (deleted products)

        return response()->json(['data' => $wishlisted]);
    }

    /**
     * Toggle wishlist (add/remove).
     * POST /api/v1/wishlist/{productId}
     */
    public function toggle(Request $request, int $productId): JsonResponse
    {
        $existing = $request->user()
            ->wishlists()
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'message' => 'Removed from wishlist',
                'wishlisted' => false,
            ]);
        }

        $request->user()->wishlists()->create([
            'product_id' => $productId,
        ]);

        return response()->json([
            'message' => 'Added to wishlist',
            'wishlisted' => true,
        ], 201);
    }
}
