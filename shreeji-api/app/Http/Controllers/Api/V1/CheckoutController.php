<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Services\DeliveryEstimateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService,
        protected DeliveryEstimateService $deliveryEstimateService,
    ) {}

    /**
     * Create an order from the current cart.
     * POST /api/v1/checkout
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'delivery_type' => ['required', 'in:express,standard,pickup'],
            'customer_note' => ['sometimes', 'string', 'max:500'],
            'po_number' => ['sometimes', 'string', 'max:100'],
            'coupon_code' => ['sometimes', 'string', 'max:50'],
        ]);

        // Verify address belongs to user
        $address = $request->user()->addresses()->findOrFail($validated['address_id']);

        // Validate express eligibility
        if ($validated['delivery_type'] === 'express' && !$address->is_express_eligible) {
            return response()->json([
                'message' => 'Express delivery is not available for this address. Only Ahmedabad city addresses are eligible.',
            ], 422);
        }

        // Get delivery estimate for shipping charge
        $deliveryOptions = $this->deliveryEstimateService->getOptions($address->pincode);
        $selectedOption = collect($deliveryOptions)->firstWhere('type', $validated['delivery_type']);
        $shippingAmount = $selectedOption['charge'] ?? 0;

        // Calculate estimated delivery
        $estimatedDelivery = $selectedOption['estimated_delivery_at'] ?? null;

        try {
            $order = $this->checkoutService->createOrder($request->user(), array_merge($validated, [
                'shipping_amount' => $shippingAmount,
                'estimated_delivery' => $estimatedDelivery,
            ]));

            return response()->json([
                'message' => 'Order created successfully',
                'data' => [
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'delivery_type' => $order->delivery_type,
                    'estimated_delivery' => $order->estimated_delivery,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Validate cart before checkout (pre-check stock, pricing).
     * POST /api/v1/checkout/validate
     */
    public function validateCheckout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'delivery_type' => ['required', 'in:express,standard,pickup'],
        ]);

        $user = $request->user();
        $cartItems = $user->cartItems()->with('sku.product')->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        $errors = [];
        foreach ($cartItems as $item) {
            if ($item->sku->available_quantity < $item->quantity) {
                $errors[] = [
                    'sku_id' => $item->sku_id,
                    'product' => $item->sku->product->name,
                    'requested' => $item->quantity,
                    'available' => $item->sku->available_quantity,
                ];
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some items have insufficient stock',
                'stock_errors' => $errors,
            ], 422);
        }

        // Get delivery options
        $address = $user->addresses()->findOrFail($validated['address_id']);
        $allInStock = $cartItems->every(fn ($item) => $item->sku->available_quantity >= $item->quantity);
        $deliveryOptions = $this->deliveryEstimateService->getOptions($address->pincode, $allInStock);

        return response()->json([
            'message' => 'Cart is valid for checkout',
            'delivery_options' => $deliveryOptions,
        ]);
    }
}
