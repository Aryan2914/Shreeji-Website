<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\ProductSku;
use App\Models\User;

class CartService
{
    /**
     * Get cart items for a user or session.
     */
    public function getItems(?int $userId = null, ?string $sessionId = null)
    {
        $query = CartItem::with(['sku.product.images', 'sku.product.category', 'sku.product.brand']);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return collect();
        }

        return $query->get();
    }

    /**
     * Add item to cart (or increment quantity if already exists).
     *
     * @throws \Exception If SKU not found or out of stock
     */
    public function addItem(int $skuId, int $quantity = 1, ?int $userId = null, ?string $sessionId = null): CartItem
    {
        $sku = ProductSku::with('product')->findOrFail($skuId);

        if (!$sku->is_active || !$sku->product->is_active) {
            throw new \Exception('This product is no longer available');
        }

        if ($sku->available_quantity < $quantity) {
            throw new \Exception("Only {$sku->available_quantity} units available");
        }

        // Check if already in cart
        $existing = CartItem::where('sku_id', $skuId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;

            if ($sku->available_quantity < $newQty) {
                throw new \Exception("Cannot add more. Only {$sku->available_quantity} units available");
            }

            $existing->update(['quantity' => $newQty]);
            return $existing->fresh();
        }

        return CartItem::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'sku_id' => $skuId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function updateQuantity(int $skuId, int $quantity, ?int $userId = null, ?string $sessionId = null): CartItem
    {
        $sku = ProductSku::findOrFail($skuId);

        if ($sku->available_quantity < $quantity) {
            throw new \Exception("Only {$sku->available_quantity} units available");
        }

        $item = CartItem::where('sku_id', $skuId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->firstOrFail();

        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(int $skuId, ?int $userId = null, ?string $sessionId = null): bool
    {
        return CartItem::where('sku_id', $skuId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->delete() > 0;
    }

    /**
     * Clear all cart items.
     */
    public function clear(?int $userId = null, ?string $sessionId = null): bool
    {
        return CartItem::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(!$userId && $sessionId, fn ($q) => $q->where('session_id', $sessionId))
            ->delete() > 0;
    }

    /**
     * Merge guest cart (session) into authenticated user's cart.
     * Called after login — guest items are moved to the user, duplicates get combined.
     */
    public function mergeGuestCart(string $sessionId, int $userId): void
    {
        $guestItems = CartItem::where('session_id', $sessionId)->get();

        foreach ($guestItems as $guestItem) {
            $userItem = CartItem::where('user_id', $userId)
                ->where('sku_id', $guestItem->sku_id)
                ->first();

            if ($userItem) {
                // Combine quantities (cap at available stock)
                $sku = ProductSku::find($guestItem->sku_id);
                $maxQty = $sku ? $sku->available_quantity : $userItem->quantity;
                $newQty = min($userItem->quantity + $guestItem->quantity, $maxQty);
                $userItem->update(['quantity' => $newQty]);
                $guestItem->delete();
            } else {
                // Move guest item to user
                $guestItem->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            }
        }
    }

    /**
     * Get cart summary (count, subtotal).
     */
    public function getSummary(?int $userId = null, ?string $sessionId = null): array
    {
        $items = $this->getItems($userId, $sessionId);

        $itemCount = $items->sum('quantity');
        $subtotal = $items->sum(function ($item) {
            return $item->sku->selling_price * $item->quantity;
        });

        return [
            'item_count' => $itemCount,
            'unique_items' => $items->count(),
            'subtotal' => round($subtotal, 2),
        ];
    }
}
