<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('attribute_key', 100);
            $table->string('attribute_value', 500);
            $table->string('attribute_unit', 50)->nullable();
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'attribute_key']);
            $table->index('attribute_key');
            $table->index(['attribute_key', 'attribute_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
