<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('manual'); // porter, manual, pickup
            $table->string('porter_order_id')->nullable();
            $table->string('tracking_url', 500)->nullable();
            $table->text('pickup_address');
            $table->text('delivery_address');
            $table->timestamp('estimated_pickup_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, assigned, picked_up, in_transit, delivered, cancelled
            $table->json('porter_response')->nullable();
            $table->timestamps();

            $table->index('porter_order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
