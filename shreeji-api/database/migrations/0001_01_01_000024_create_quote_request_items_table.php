<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->string('product_description', 500);
            $table->foreignId('sku_id')->nullable()->constrained('product_skus')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index('quote_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_request_items');
    }
};
