<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compatible_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('compatibility_type', 100)->default('works_with');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'compatible_product_id', 'compatibility_type'], 'product_compat_unique');
            $table->index('compatible_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_compatibilities');
    }
};
