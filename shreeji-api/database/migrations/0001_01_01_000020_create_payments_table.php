<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->string('method', 50)->nullable(); // upi, card, netbanking, wallet
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('pending'); // pending, authorized, captured, failed, refunded
            $table->timestamp('paid_at')->nullable();
            $table->string('refund_id')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index('razorpay_order_id');
            $table->index('razorpay_payment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
