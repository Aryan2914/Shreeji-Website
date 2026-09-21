<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\ProductSku;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Reserve stock for an order (during checkout, before payment).
     * Uses database row locking to prevent race conditions.
     *
     * @throws \Exception If insufficient stock
     */
    public function reserve(int $skuId, int $quantity, int $orderId, ?int $userId = null): void
    {
        DB::transaction(function () use ($skuId, $quantity, $orderId, $userId) {
            // Lock the SKU row for update to prevent concurrent modifications
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            $available = $sku->stock_quantity - $sku->reserved_quantity;

            if ($available < $quantity) {
                throw new \Exception(
                    "Insufficient stock for SKU {$sku->sku}. Available: {$available}, Requested: {$quantity}"
                );
            }

            // Increment reserved quantity
            $sku->increment('reserved_quantity', $quantity);

            // Log the movement
            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::RESERVED,
                'quantity' => -$quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'note' => "Reserved for order #{$orderId}",
                'performed_by' => $userId,
            ]);
        });
    }

    /**
     * Release reserved stock (order cancelled, payment failed, or timeout).
     */
    public function release(int $skuId, int $quantity, int $orderId, ?int $userId = null): void
    {
        DB::transaction(function () use ($skuId, $quantity, $orderId, $userId) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            // Decrement reserved (don't go below 0)
            $releaseQty = min($quantity, $sku->reserved_quantity);
            $sku->decrement('reserved_quantity', $releaseQty);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::RELEASED,
                'quantity' => $releaseQty,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'note' => "Released from order #{$orderId}",
                'performed_by' => $userId,
            ]);
        });
    }

    /**
     * Confirm sale — convert reserved stock to actual decrement.
     * Called when payment is confirmed.
     */
    public function confirmSale(int $skuId, int $quantity, int $orderId, ?int $userId = null): void
    {
        DB::transaction(function () use ($skuId, $quantity, $orderId, $userId) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            // Decrement both stock and reserved
            $sku->decrement('stock_quantity', $quantity);
            $sku->decrement('reserved_quantity', $quantity);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::SALE,
                'quantity' => -$quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'note' => "Sold via order #{$orderId}",
                'performed_by' => $userId,
            ]);

            // Check for low stock alert
            $sku->refresh();
            if ($sku->is_low_stock) {
                event(new \App\Events\StockLow($sku));
            }
        });
    }

    /**
     * Manual stock adjustment (admin adding/removing stock).
     */
    public function adjust(int $skuId, int $quantity, string $note, int $performedBy): void
    {
        DB::transaction(function () use ($skuId, $quantity, $note, $performedBy) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            $sku->increment('stock_quantity', $quantity);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::ADJUSTMENT,
                'quantity' => $quantity,
                'reference_type' => 'manual',
                'note' => $note,
                'performed_by' => $performedBy,
            ]);
        });
    }

    /**
     * Add stock from purchase/restock.
     */
    public function restock(int $skuId, int $quantity, string $note, int $performedBy): void
    {
        DB::transaction(function () use ($skuId, $quantity, $note, $performedBy) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            $sku->increment('stock_quantity', $quantity);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::PURCHASE,
                'quantity' => $quantity,
                'reference_type' => 'manual',
                'note' => $note,
                'performed_by' => $performedBy,
            ]);
        });
    }

    /**
     * Process a return — add stock back.
     */
    public function processReturn(int $skuId, int $quantity, int $orderId, ?int $userId = null): void
    {
        DB::transaction(function () use ($skuId, $quantity, $orderId, $userId) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            $sku->increment('stock_quantity', $quantity);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::RETURN,
                'quantity' => $quantity,
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'note' => "Returned from order #{$orderId}",
                'performed_by' => $userId,
            ]);
        });
    }

    /**
     * Showroom walk-in sale (decrements stock without an online order).
     */
    public function showroomSale(int $skuId, int $quantity, string $note, int $performedBy): void
    {
        DB::transaction(function () use ($skuId, $quantity, $note, $performedBy) {
            $sku = ProductSku::lockForUpdate()->findOrFail($skuId);

            $available = $sku->stock_quantity - $sku->reserved_quantity;

            if ($available < $quantity) {
                throw new \Exception(
                    "Insufficient stock for showroom sale. Available: {$available}, Requested: {$quantity}"
                );
            }

            $sku->decrement('stock_quantity', $quantity);

            StockMovement::create([
                'sku_id' => $skuId,
                'type' => StockMovementType::SALE,
                'quantity' => -$quantity,
                'reference_type' => 'pos',
                'note' => "Showroom sale: {$note}",
                'performed_by' => $performedBy,
            ]);

            // Check for low stock alert
            $sku->refresh();
            if ($sku->is_low_stock) {
                event(new \App\Events\StockLow($sku));
            }
        });
    }

    /**
     * Check if enough stock is available for a given quantity.
     */
    public function isAvailable(int $skuId, int $quantity): bool
    {
        $sku = ProductSku::find($skuId);

        if (!$sku) {
            return false;
        }

        return ($sku->stock_quantity - $sku->reserved_quantity) >= $quantity;
    }

    /**
     * Get all low-stock SKUs (available qty ≤ min_stock_alert).
     */
    public function getLowStockSkus()
    {
        return ProductSku::active()
            ->lowStock()
            ->with('product:id,name,slug')
            ->get();
    }

    /**
     * Get all out-of-stock SKUs.
     */
    public function getOutOfStockSkus()
    {
        return ProductSku::active()
            ->outOfStock()
            ->with('product:id,name,slug')
            ->get();
    }
}
