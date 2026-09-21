<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('gstin', 15);
            $table->string('pan', 10)->nullable();
            $table->unsignedBigInteger('billing_address_id')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_used', 12, 2)->default(0);
            $table->string('price_tier')->default('business'); // retail, business, bulk
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('gstin');
            $table->index('price_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
