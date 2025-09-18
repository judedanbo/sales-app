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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade')->comment('Associated sale transaction');
            $table->foreignId('product_id')->constrained('products')->comment('Sold product');

            // Item details
            $table->integer('quantity')->comment('Quantity sold');
            $table->decimal('unit_price', 10, 2)->comment('Price per unit at time of sale');
            $table->decimal('line_total', 10, 2)->comment('Total for this line (quantity × unit_price)');
            $table->decimal('tax_rate', 5, 4)->default(0.0000)->comment('Tax rate applied (as decimal)');
            $table->decimal('tax_amount', 10, 2)->default(0.00)->comment('Tax amount for this line');

            // Product snapshot for historical accuracy
            $table->json('product_snapshot')->nullable()->comment('Product details at time of sale (name, sku, category, etc.)');

            // Inventory tracking
            $table->boolean('inventory_updated')->default(false)->comment('Whether inventory was decremented for this item');
            $table->timestamp('inventory_updated_at')->nullable()->comment('When inventory was updated');

            // Audit fields
            $table->timestamps();

            // Indexes for performance
            $table->index(['sale_id'], 'sale_items_sale_id_index');
            $table->index(['product_id'], 'sale_items_product_id_index');
            $table->index(['created_at'], 'sale_items_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
