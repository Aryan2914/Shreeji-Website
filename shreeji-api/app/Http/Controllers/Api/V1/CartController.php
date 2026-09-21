<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected PricingService $pricingService,
    ) {}

    /**
     * Get cart items.
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $this->resolveSessionId($request);

        $items = $this->cartService->getItems($userId, $sessionId);

        // Calculate pricing
        $user = $request->user();
        $lineItems = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $pricing = $this->pricingService->resolvePrice($item->sku, $user, $item->quantity);
            $lineItems[] = [
                'id' => $item->id,
                'sku_id' => $item->sku_id,
                'sku_code' => $item->sku->sku,
                'product_id' => $item->sku->product_id,
                'product_name' => $item->sku->product->name,
                'product_slug' => $item->sku->product->slug,
                'variant_label' => $item->sku->variant_label,
                'image' => $item->sku->product->images->first()?->url,
                'category' => $item->sku->product->category?->name,
                'brand' => $item->sku->product->brand?->name,
                'quantity' => $item->quantity,
                'unit_price' => $pricing['unit_price'],
                'retail_price' => $pricing['retail_price'],
                'line_total' => $pricing['line_total'],
                'savings' => $pricing['savings'] * $item->quantity,
                'gst_rate' => $item->sku->product->gst_rate,
                'available_quantity' => $item->sku->available_quantity,
                'is_express_eligible' => $item->sku->product->is_express_eligible,
            ];
            $subtotal += $pricing['line_total'];
        }

        $summary = $this->cartService->getSummary($userId, $sessionId);

        return response()->json([
            'data' => $lineItems,
            'summary' => [
                'item_count' => $summary['item_count'],
                'unique_items' => $summary['unique_items'],
                'subtotal' => round($subtotal, 2),
            ],
        ]);
    }

    /**
     * Add item to cart.
     * POST /api/v1/cart
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $item = $this->cartService->addItem(
                $validated['sku_id'],
                $validated['quantity'] ?? 1,
                $request->user()?->id,
                $this->resolveSessionId($request)
            );

            return response()->json([
                'message' => 'Item added to cart',
                'data' => $item->load('sku.product'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Update cart item quantity.
     * PUT /api/v1/cart/{skuId}
     */
    public function update(Request $request, int $skuId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $item = $this->cartService->updateQuantity(
                $skuId,
                $validated['quantity'],
                $request->user()?->id,
                $this->resolveSessionId($request)
            );

            return response()->json([
                'message' => 'Cart updated',
                'data' => $item->load('sku.product'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove item from cart.
     * DELETE /api/v1/cart/{skuId}
     */
    public function destroy(Request $request, int $skuId): JsonResponse
    {
        $this->cartService->removeItem(
            $skuId,
            $request->user()?->id,
            $this->resolveSessionId($request)
        );

        return response()->json(['message' => 'Item removed from cart']);
    }

    /**
     * Clear entire cart.
     * DELETE /api/v1/cart
     */
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clear(
            $request->user()?->id,
            $this->resolveSessionId($request)
        );

        return response()->json(['message' => 'Cart cleared']);
    }

    /**
     * Merge guest cart into authenticated user's cart.
     * POST /api/v1/cart/merge
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        $this->cartService->mergeGuestCart(
            $validated['session_id'],
            $request->user()->id
        );

        return response()->json(['message' => 'Cart merged successfully']);
    }

    /**
     * Resolve and validate session ID for guest carts.
     * Rejects non-UUID strings to prevent guessing other users' carts.
     */
    private function resolveSessionId(Request $request): ?string
    {
        if ($request->user()) {
            return null;
        }

        $sessionId = $request->header('X-Session-Id');
        if (!$sessionId || !\Illuminate\Support\Str::isUuid($sessionId)) {
            abort(response()->json([
                'message' => 'Invalid or missing X-Session-Id header. Guest carts require a valid UUID.'
            ], 422));
        }

        return $sessionId;
    }
}
