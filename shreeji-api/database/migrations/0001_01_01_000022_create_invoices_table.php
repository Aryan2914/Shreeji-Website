<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('seller_gstin', 15);
            $table->string('buyer_gstin', 15)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('pdf_url', 500)->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamps();

            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
