<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\StockService;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function (StockService $stockService) {
    // Release unpaid reservations after 30 minutes
    $cutoff = now()->subMinutes(30);
    $expiredOrders = Order::whereIn('status', [OrderStatus::NEW, OrderStatus::PAYMENT_PENDING])
        ->whereDoesntHave('payment', function ($q) {
            $q->where('status', \App\Enums\PaymentStatus::CAPTURED);
        })
        ->where('created_at', '<=', $cutoff)
        ->with('items')
        ->get();

    foreach ($expiredOrders as $order) {
        foreach ($order->items as $item) {
            $stockService->release($item->sku_id, $item->quantity, $order->id, null);
        }
        $order->transitionTo(OrderStatus::CANCELLED, 'Auto-cancelled: Payment window expired (30m timeout)');
    }
})->everyMinute()->name('release-unpaid-reservations');
