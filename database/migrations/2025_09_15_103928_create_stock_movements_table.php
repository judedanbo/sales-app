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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('cascade');

            // Movement type and details
            $table->enum('type', [
                'initial_stock',      // Initial inventory setup
                'purchase',          // Stock received from supplier
                'sale',              // Stock sold to customer
                'return_from_customer', // Customer returned item
                'return_to_supplier', // Returned to supplier
                'adjustment',        // Manual stock adjustment
                'transfer_in',       // Transfer from another location
                'transfer_out',      // Transfer to another location
                'damaged',           // Stock marked as damaged
                'expired',           // Stock expired
                'theft',             // Stock stolen/lost
                'manufacturing',     // Stock from production
                'reservation',       // Stock reserved for order
                'release_reservation', // Reserved stock released
            ]);

            // Quantity changes (positive = increase, negative = decrease)
            $table->integer('quantity_change')->comment('Positive for increase, negative for decrease');
            $table->integer('quantity_before')->comment('Stock level before this movement');
            $table->integer('quantity_after')->comment('Stock level after this movement');

            // Cost and pricing information
            $table->decimal('unit_cost', 10, 2)->nullable()->comment('Cost per unit for this movement');
            $table->decimal('total_cost', 10, 2)->nullable()->comment('Total cost of movement');
            $table->string('currency', 3)->default('GHS')->comment('Currency code');

            // Reference information
            $table->string('reference_type')->nullable()->comment('Type of reference document');
            $table->string('reference_id')->nullable()->comment('ID of reference document');
            $table->text('notes')->nullable()->comment('Additional notes about the movement');
            $table->json('metadata')->nullable()->comment('Additional data (supplier info, customer info, etc.)');

            // Location and batching
            $table->string('location')->nullable()->comment('Warehouse/location where movement occurred');
            $table->string('batch_number')->nullable()->comment('Batch/lot number');
            $table->date('expiry_date')->nullable()->comment('Expiry date for perishable items');

            // User and timing
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('User who made the movement');
            $table->timestamp('movement_date')->useCurrent()->comment('When the movement occurred');
            $table->boolean('is_confirmed')->default(true)->comment('Whether movement is confirmed');
            $table->timestamp('confirmed_at')->nullable()->comment('When movement was confirmed');

            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'movement_date']);
            $table->index(['product_variant_id', 'movement_date']);
            $table->index(['type', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['location', 'movement_date']);
            $table->index(['batch_number']);
            $table->index(['is_confirmed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
