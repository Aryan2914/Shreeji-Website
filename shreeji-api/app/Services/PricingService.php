<?php

namespace App\Services;

use App\Models\ProductSku;
use App\Models\User;

class PricingService
{
    /**
     * Resolve the effective price for a SKU based on user's tier and quantity.
     *
     * Priority:
     * 1. Price tier match (tier + quantity bracket) → use tier price
     * 2. No tier match → use SKU selling_price (retail default)
     */
    public function resolvePrice(ProductSku $sku, ?User $user = null, int $quantity = 1): array
    {
        $tier = $this->getUserTier($user);
        $unitPrice = $sku->getPriceForTier($tier, $quantity);
        $retailPrice = (float) $sku->retail_price;
        $sellingPrice = (float) $sku->selling_price;

        // Calculate savings
        $savings = $retailPrice - $unitPrice;
        $savingsPercent = $retailPrice > 0
            ? round(($savings / $retailPrice) * 100, 1)
            : 0;

        return [
            'unit_price' => $unitPrice,
            'retail_price' => $retailPrice,
            'selling_price' => $sellingPrice,
            'tier' => $tier,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
            'savings' => round($savings, 2),
            'savings_percent' => $savingsPercent,
            'is_discounted' => $unitPrice < $retailPrice,
        ];
    }

    /**
     * Calculate GST breakdown for a line item.
     */
    public function calculateTax(float $unitPrice, int $quantity, float $gstRate, string $buyerState = 'Gujarat'): array
    {
        $taxableAmount = round($unitPrice * $quantity, 2);
        $totalTax = round($taxableAmount * ($gstRate / 100), 2);

        $sellerState = config('shreeji.company.state', 'Gujarat');
        $isIntraState = strtolower($buyerState) === strtolower($sellerState);

        if ($isIntraState) {
            // Intra-state: split into CGST + SGST (each half of GST rate)
            $halfTax = round($totalTax / 2, 2);
            return [
                'taxable_amount' => $taxableAmount,
                'gst_rate' => $gstRate,
                'cgst_rate' => $gstRate / 2,
                'cgst_amount' => $halfTax,
                'sgst_rate' => $gstRate / 2,
                'sgst_amount' => $halfTax,
                'igst_rate' => 0,
                'igst_amount' => 0,
                'total_tax' => $halfTax * 2, // Avoids rounding mismatch
                'total_with_tax' => $taxableAmount + ($halfTax * 2),
                'is_intra_state' => true,
            ];
        }

        // Inter-state: full IGST
        return [
            'taxable_amount' => $taxableAmount,
            'gst_rate' => $gstRate,
            'cgst_rate' => 0,
            'cgst_amount' => 0,
            'sgst_rate' => 0,
            'sgst_amount' => 0,
            'igst_rate' => $gstRate,
            'igst_amount' => $totalTax,
            'total_tax' => $totalTax,
            'total_with_tax' => $taxableAmount + $totalTax,
            'is_intra_state' => false,
        ];
    }

    /**
     * Calculate full cart pricing with GST.
     *
     * @param array $items Array of ['sku' => ProductSku, 'quantity' => int]
     */
    public function calculateCartTotal(array $items, ?User $user = null, string $buyerState = 'Gujarat'): array
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;
        $lineItems = [];

        foreach ($items as $item) {
            /** @var ProductSku $sku */
            $sku = $item['sku'];
            $qty = $item['quantity'];

            $pricing = $this->resolvePrice($sku, $user, $qty);
            $gstRate = $sku->product->gst_rate ?? 18;
            $tax = $this->calculateTax($pricing['unit_price'], $qty, $gstRate, $buyerState);

            $subtotal += $tax['taxable_amount'];
            $totalTax += $tax['total_tax'];
            $totalCgst += $tax['cgst_amount'];
            $totalSgst += $tax['sgst_amount'];
            $totalIgst += $tax['igst_amount'];

            $lineItems[] = [
                'sku_id' => $sku->id,
                'sku_code' => $sku->sku,
                'product_name' => $sku->product->name,
                'quantity' => $qty,
                'unit_price' => $pricing['unit_price'],
                'retail_price' => $pricing['retail_price'],
                'gst_rate' => $gstRate,
                'hsn_code' => $sku->product->hsn_code,
                'tax' => $tax,
                'line_total' => $tax['total_with_tax'],
            ];
        }

        return [
            'items' => $lineItems,
            'subtotal' => round($subtotal, 2),
            'cgst_total' => round($totalCgst, 2),
            'sgst_total' => round($totalSgst, 2),
            'igst_total' => round($totalIgst, 2),
            'tax_total' => round($totalTax, 2),
            'shipping' => 0, // Calculated separately by DeliveryEstimateService
            'discount' => 0, // Calculated separately by coupon logic
            'grand_total' => round($subtotal + $totalTax, 2),
            'is_intra_state' => strtolower($buyerState) === strtolower(config('shreeji.company.state', 'Gujarat')),
            'item_count' => count($lineItems),
            'total_quantity' => array_sum(array_column($lineItems, 'quantity')),
        ];
    }

    /**
     * Get the price tier for a user.
     */
    private function getUserTier(?User $user): string
    {
        if (!$user) {
            return 'retail';
        }

        return $user->getPriceTier();
    }
}
