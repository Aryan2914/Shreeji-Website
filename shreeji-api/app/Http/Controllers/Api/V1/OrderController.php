<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
    ) {}

    /**
     * List user's orders (paginated).
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with([
                'items:id,order_id,product_name,quantity,total',
                'payment:id,order_id,status,method',
                'delivery:id,order_id,provider,status',
            ])
            ->recent()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get single order detail.
     * GET /api/v1/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with([
                'items.sku.product.images',
                'address',
                'payment',
                'delivery',
                'invoice',
                'statusHistory' => fn ($q) => $q->orderByDesc('created_at'),
            ])
            ->firstOrFail();

        return response()->json(['data' => $order]);
    }

    /**
     * Cancel an order.
     * POST /api/v1/orders/{orderNumber}/cancel
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$order->canBeCancelled()) {
            return response()->json([
                'message' => 'This order cannot be cancelled. Current status: ' . $order->status->label(),
            ], 422);
        }

        $this->checkoutService->cancelOrder($order, $validated['reason'], $request->user()->id);

        return response()->json([
            'message' => 'Order cancelled successfully',
            'data' => $order->fresh(),
        ]);
    }
}
