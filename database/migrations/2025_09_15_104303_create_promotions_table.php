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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Promotion name');
            $table->string('code')->unique()->nullable()->comment('Promotion code/coupon code');
            $table->enum('type', [
                'percentage_discount',  // Percentage off
                'fixed_discount',      // Fixed amount off
                'bogo',               // Buy one get one
                'free_shipping',      // Free shipping
                'bundle_deal',        // Bundle deals
                'flash_sale',         // Time-limited sale
                'loyalty_points',     // Loyalty points multiplier
                'referral',           // Referral bonus
                'seasonal',           // Seasonal promotion
                'clearance',          // Clearance sale
            ])->comment('Type of promotion');

            // Discount configuration
            $table->decimal('discount_value', 10, 4)->nullable()->comment('Discount percentage or amount');
            $table->decimal('minimum_purchase', 10, 2)->nullable()->comment('Minimum purchase amount');
            $table->decimal('maximum_discount', 10, 2)->nullable()->comment('Maximum discount amount');

            // BOGO configuration
            $table->integer('buy_quantity')->nullable()->comment('Quantity to buy for BOGO deals');
            $table->integer('get_quantity')->nullable()->comment('Quantity to get for BOGO deals');
            $table->decimal('get_discount_percentage', 5, 2)->nullable()->comment('Discount on get items (0-100)');

            // Applicable products/categories
            $table->enum('applies_to', ['all_products', 'specific_products', 'categories', 'variants'])->default('all_products');
            $table->json('applicable_product_ids')->nullable()->comment('Product IDs this promotion applies to');
            $table->json('applicable_category_ids')->nullable()->comment('Category IDs this promotion applies to');
            $table->json('applicable_variant_ids')->nullable()->comment('Variant IDs this promotion applies to');

            // Excluded items
            $table->json('excluded_product_ids')->nullable()->comment('Products excluded from promotion');
            $table->json('excluded_category_ids')->nullable()->comment('Categories excluded from promotion');

            // Validity and constraints
            $table->datetime('starts_at')->comment('Promotion start date and time');
            $table->datetime('ends_at')->comment('Promotion end date and time');
            $table->integer('usage_limit')->nullable()->comment('Total usage limit for promotion');
            $table->integer('usage_count')->default(0)->comment('Current usage count');
            $table->integer('per_customer_limit')->nullable()->comment('Max uses per customer');

            // Customer targeting
            $table->enum('customer_eligibility', ['all_customers', 'new_customers', 'returning_customers', 'vip_customers', 'specific_customers'])->default('all_customers');
            $table->json('eligible_customer_ids')->nullable()->comment('Specific eligible customers');
            $table->json('customer_groups')->nullable()->comment('Customer groups eligible for promotion');

            // Status and display
            $table->boolean('is_active')->default(true)->comment('Whether promotion is active');
            $table->boolean('is_public')->default(true)->comment('Whether promotion is publicly visible');
            $table->boolean('stackable')->default(false)->comment('Can be combined with other promotions');
            $table->boolean('auto_apply')->default(false)->comment('Automatically apply when conditions met');

            // Display information
            $table->text('description')->nullable()->comment('Promotion description');
            $table->text('terms_and_conditions')->nullable()->comment('Terms and conditions');
            $table->string('banner_image')->nullable()->comment('Promotion banner image');
            $table->json('display_rules')->nullable()->comment('Display rules and conditions');

            // Analytics and tracking
            $table->string('utm_campaign')->nullable()->comment('UTM campaign tracking');
            $table->string('utm_source')->nullable()->comment('UTM source tracking');
            $table->string('utm_medium')->nullable()->comment('UTM medium tracking');
            $table->json('analytics_data')->nullable()->comment('Additional analytics data');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index(['starts_at', 'ends_at']);
            $table->index(['is_active', 'is_public']);
            $table->index(['code']);
            $table->index(['applies_to', 'is_active']);
            $table->index(['customer_eligibility', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
