<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Support\Carbon;

class DeliveryEstimateService
{
    /**
     * Get delivery options for a given pincode.
     *
     * Returns available delivery methods with time estimates and charges.
     */
    public function getOptions(string $pincode, bool $allItemsInStock = true): array
    {
        $options = [];

        // ── Express Delivery (Ahmedabad only) ──────────
        $isExpressEligible = in_array($pincode, Address::EXPRESS_PINCODES);

        if ($isExpressEligible && $allItemsInStock && config('shreeji.express.enabled', true)) {
            $expressEstimate = $this->calculateExpressEstimate();

            if ($expressEstimate) {
                $options[] = [
                    'type' => 'express',
                    'label' => config('shreeji.express.label', 'Ahmedabad Express'),
                    'description' => $expressEstimate['description'],
                    'estimated_time' => $expressEstimate['estimated_time'],
                    'estimated_delivery_at' => $expressEstimate['estimated_delivery_at'],
                    'charge' => $this->getExpressCharge(),
                    'is_available' => $expressEstimate['is_available'],
                    'unavailable_reason' => $expressEstimate['unavailable_reason'],
                ];
            }
        }

        // ── Standard Delivery ──────────────────────────
        $standardEstimate = $this->calculateStandardEstimate($pincode);
        $options[] = [
            'type' => 'standard',
            'label' => config('shreeji.standard.label', 'Standard Delivery'),
            'description' => $standardEstimate['description'],
            'estimated_time' => $standardEstimate['estimated_time'],
            'estimated_delivery_at' => $standardEstimate['estimated_delivery_at'],
            'charge' => 0.00,
            'is_available' => true,
            'unavailable_reason' => null,
        ];

        // ── Showroom Pickup ────────────────────────────
        $options[] = [
            'type' => 'pickup',
            'label' => 'Showroom Pickup',
            'description' => 'Pick up from Shreeji Infotech, Maninagar, Ahmedabad',
            'estimated_time' => $allItemsInStock ? 'Ready in 15 min' : 'Ready in 1-2 hours',
            'estimated_delivery_at' => $allItemsInStock
                ? now()->addMinutes(15)->toISOString()
                : now()->addHours(2)->toISOString(),
            'charge' => 0.00,
            'is_available' => true,
            'unavailable_reason' => null,
        ];

        return $options;
    }

    /**
     * Check if a pincode is eligible for express delivery.
     */
    public function isExpressEligible(string $pincode): bool
    {
        return in_array($pincode, Address::EXPRESS_PINCODES);
    }

    /**
     * Calculate express delivery time estimate.
     */
    private function calculateExpressEstimate(): ?array
    {
        $now = Carbon::now('Asia/Kolkata');
        $startTime = Carbon::parse(config('shreeji.express.operating_hours.start', '09:00'), 'Asia/Kolkata');
        $endTime = Carbon::parse(config('shreeji.express.operating_hours.end', '20:00'), 'Asia/Kolkata');
        $cutoffTime = Carbon::parse(config('shreeji.express.same_day_cutoff', '18:00'), 'Asia/Kolkata');

        $minMinutes = config('shreeji.express.min_minutes', 30);
        $maxMinutes = config('shreeji.express.max_minutes', 90);

        // Check if within operating hours
        if ($now->lt($startTime)) {
            // Before opening — delivery will start after store opens
            $estimatedDelivery = $startTime->copy()->addMinutes($maxMinutes);
            return [
                'is_available' => true,
                'description' => "Express delivery after {$startTime->format('g:i A')} today ({$minMinutes}-{$maxMinutes} min)",
                'estimated_time' => "{$minMinutes}-{$maxMinutes} min",
                'estimated_delivery_at' => $estimatedDelivery->toISOString(),
                'unavailable_reason' => null,
            ];
        }

        if ($now->gt($endTime)) {
            // After closing — next day delivery
            $tomorrow = $now->copy()->addDay()->setTimeFromTimeString($startTime->format('H:i'));
            $estimatedDelivery = $tomorrow->addMinutes($maxMinutes);
            return [
                'is_available' => true,
                'description' => "Express delivery tomorrow by {$estimatedDelivery->format('g:i A')}",
                'estimated_time' => 'Tomorrow ' . $startTime->format('g:i A') . " - {$estimatedDelivery->format('g:i A')}",
                'estimated_delivery_at' => $estimatedDelivery->toISOString(),
                'unavailable_reason' => null,
            ];
        }

        if ($now->gt($cutoffTime)) {
            // After cutoff but before close — same day possible but tight
            $estimatedDelivery = $now->copy()->addMinutes($maxMinutes);
            return [
                'is_available' => true,
                'description' => "Express delivery today by {$estimatedDelivery->format('g:i A')}",
                'estimated_time' => "{$minMinutes}-{$maxMinutes} min",
                'estimated_delivery_at' => $estimatedDelivery->toISOString(),
                'unavailable_reason' => null,
            ];
        }

        // Within normal operating hours
        $estimatedDelivery = $now->copy()->addMinutes($maxMinutes);
        return [
            'is_available' => true,
            'description' => "Express delivery in {$minMinutes}-{$maxMinutes} minutes",
            'estimated_time' => "{$minMinutes}-{$maxMinutes} min",
            'estimated_delivery_at' => $estimatedDelivery->toISOString(),
            'unavailable_reason' => null,
        ];
    }

    /**
     * Calculate standard delivery estimate.
     */
    private function calculateStandardEstimate(string $pincode): array
    {
        $isAhmedabad = in_array($pincode, Address::EXPRESS_PINCODES);
        $minDays = $isAhmedabad ? 1 : config('shreeji.standard.min_days', 1);
        $maxDays = $isAhmedabad ? 1 : config('shreeji.standard.max_days', 3);

        $estimatedDelivery = now()->addDays($maxDays);

        // Skip Sunday
        while ($estimatedDelivery->isSunday()) {
            $estimatedDelivery->addDay();
        }

        $description = $minDays === $maxDays
            ? "Delivery by {$estimatedDelivery->format('D, M j')}"
            : "Delivery in {$minDays}-{$maxDays} business days";

        return [
            'description' => $description,
            'estimated_time' => "{$minDays}-{$maxDays} days",
            'estimated_delivery_at' => $estimatedDelivery->toISOString(),
        ];
    }

    /**
     * Get express delivery charge based on order total.
     */
    private function getExpressCharge(float $orderTotal = 0): float
    {
        $freeAbove = config('shreeji.express.free_above', 999);

        if ($orderTotal >= $freeAbove) {
            return 0.00;
        }

        return config('shreeji.express.base_charge', 49.00);
    }
}
