<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Rule name/description');
            $table->enum('type', [
                'bulk_discount',      // Volume-based pricing
                'customer_tier',      // Customer group pricing
                'time_based',        // Time-sensitive pricing (peak hours, seasonal)
                'quantity_break',    // Quantity break pricing
                'bundle',            // Bundle pricing
                'loyalty',           // Loyalty-based pricing
                'geographic',        // Location-based pricing
                'competitor_match',  // Competitor price matching
                'dynamic',           // AI/algorithm-based dynamic pricing
            ])->comment('Type of pricing rule');

            // Rule configuration
            $table->json('conditions')->comment('Conditions that must be met for rule to apply');
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'fixed_price'])->comment('Type of discount');
            $table->decimal('discount_value', 10, 4)->comment('Discount value (percentage or amount)');
            $table->decimal('minimum_quantity', 10, 2)->nullable()->comment('Minimum quantity required');
            $table->decimal('maximum_quantity', 10, 2)->nullable()->comment('Maximum quantity for rule');
            $table->decimal('minimum_amount', 10, 2)->nullable()->comment('Minimum order amount');
            $table->decimal('maximum_amount', 10, 2)->nullable()->comment('Maximum order amount');

            // Applicable products/categories
            $table->enum('applies_to', ['all_products', 'specific_products', 'categories', 'variants'])->default('all_products');
            $table->json('applicable_product_ids')->nullable()->comment('Product IDs this rule applies to');
            $table->json('applicable_category_ids')->nullable()->comment('Category IDs this rule applies to');
            $table->json('applicable_variant_ids')->nullable()->comment('Variant IDs this rule applies to');

            // Customer targeting
            $table->enum('customer_scope', ['all_customers', 'customer_groups', 'specific_customers'])->default('all_customers');
            $table->json('customer_group_ids')->nullable()->comment('Customer groups this rule applies to');
            $table->json('customer_ids')->nullable()->comment('Specific customers this rule applies to');

            // Time and location constraints
            $table->datetime('valid_from')->nullable()->comment('Rule validity start date');
            $table->datetime('valid_to')->nullable()->comment('Rule validity end date');
            $table->json('time_constraints')->nullable()->comment('Time-based constraints (hours, days of week)');
            $table->json('location_constraints')->nullable()->comment('Geographic constraints');

            // Priority and limits
            $table->integer('priority')->default(0)->comment('Rule priority (higher number = higher priority)');
            $table->integer('usage_limit')->nullable()->comment('Maximum number of times rule can be used');
            $table->integer('usage_count')->default(0)->comment('Current usage count');
            $table->integer('per_customer_limit')->nullable()->comment('Max uses per customer');

            // Status and metadata
            $table->boolean('is_active')->default(true)->comment('Whether rule is currently active');
            $table->boolean('stackable')->default(false)->comment('Can be combined with other rules');
            $table->text('description')->nullable()->comment('Rule description');
            $table->json('metadata')->nullable()->comment('Additional rule configuration');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['valid_from', 'valid_to']);
            $table->index(['priority', 'is_active']);
            $table->index(['applies_to', 'is_active']);
            $table->index(['customer_scope', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
