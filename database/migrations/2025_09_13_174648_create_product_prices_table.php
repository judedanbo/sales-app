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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            // Product relationship
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('Product this price belongs to');

            // Price versioning
            $table->integer('version_number')->default(1)->comment('Price version number');
            $table->decimal('price', 10, 2)->comment('Product price excluding tax');
            $table->decimal('final_price', 10, 2)->comment('Final price including tax and adjustments');

            // Price validity
            $table->enum('status', ['draft', 'pending', 'active', 'expired', 'rejected'])->default('draft')->comment('Price status');
            $table->datetime('valid_from')->comment('When this price becomes effective');
            $table->datetime('valid_to')->nullable()->comment('When this price expires');

            // Approval workflow
            $table->foreignId('created_by')->constrained('users')->comment('User who created this price');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who approved this price');
            $table->datetime('approved_at')->nullable()->comment('When this price was approved');
            $table->text('approval_notes')->nullable()->comment('Notes from approval process');

            // Additional pricing metadata
            $table->decimal('cost_price', 10, 2)->nullable()->comment('Product cost price for margin calculation');
            $table->decimal('markup_percentage', 5, 2)->nullable()->comment('Markup percentage over cost');
            $table->string('currency', 3)->default('USD')->comment('Price currency code');
            $table->json('bulk_discounts')->nullable()->comment('Bulk discount tiers');
            $table->text('notes')->nullable()->comment('Internal notes about this price version');

            $table->timestamps();

            // Indexes for performance
            $table->index(['product_id', 'status', 'valid_from'], 'product_prices_lookup_index');
            $table->index(['valid_from', 'valid_to'], 'product_prices_validity_index');
            $table->index(['status'], 'product_prices_status_index');

            // Unique constraint to prevent duplicate active prices for same period
            $table->unique(['product_id', 'version_number'], 'product_prices_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
