<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\DeliveryEstimateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct(
        protected DeliveryEstimateService $deliveryEstimateService,
    ) {}

    /**
     * Get delivery estimate for a pincode.
     * POST /api/v1/delivery/estimate
     */
    public function estimate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => ['required', 'string', 'regex:/^[1-9][0-9]{5}$/'],
            'all_in_stock' => ['sometimes', 'boolean'],
        ]);

        $options = $this->deliveryEstimateService->getOptions(
            $validated['pincode'],
            $validated['all_in_stock'] ?? true
        );

        return response()->json([
            'data' => $options,
            'is_express_eligible' => $this->deliveryEstimateService->isExpressEligible($validated['pincode']),
        ]);
    }

    /**
     * Quick check: is express available for this pincode?
     * GET /api/v1/delivery/check-express/{pincode}
     */
    public function checkExpress(string $pincode): JsonResponse
    {
        $isEligible = $this->deliveryEstimateService->isExpressEligible($pincode);

        return response()->json([
            'pincode' => $pincode,
            'express_eligible' => $isEligible,
            'message' => $isEligible
                ? '⚡ Ahmedabad Express available — delivery in 30-90 minutes!'
                : 'Standard delivery available for this pincode.',
        ]);
    }
}
