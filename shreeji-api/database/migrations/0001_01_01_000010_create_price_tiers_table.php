<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->string('tier'); // retail, business, bulk
            $table->integer('min_quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['sku_id', 'tier', 'min_quantity']);
            $table->index('tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
    }
};
