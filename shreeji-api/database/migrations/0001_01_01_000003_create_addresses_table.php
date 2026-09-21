<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Home');
            $table->string('contact_name');
            $table->string('contact_phone', 15);
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city', 100)->default('Ahmedabad');
            $table->string('state', 100)->default('Gujarat');
            $table->string('pincode', 6);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_express_eligible')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index('pincode');
        });

        // Add foreign key for business_profiles.billing_address_id now that addresses table exists
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->foreign('billing_address_id')
                  ->references('id')
                  ->on('addresses')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropForeign(['billing_address_id']);
        });
        Schema::dropIfExists('addresses');
    }
};
