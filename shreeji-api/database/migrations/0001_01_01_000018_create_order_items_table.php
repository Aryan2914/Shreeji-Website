<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sku_id')->constrained('product_skus')->restrictOnDelete();
            $table->string('product_name'); // Snapshot at order time
            $table->string('sku_code', 100); // Snapshot
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Excl. tax
            $table->decimal('tax_rate', 4, 2); // GST % at order time
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total', 10, 2); // (unit_price × qty) + tax
            $table->string('hsn_code', 8)->nullable(); // Snapshot
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
