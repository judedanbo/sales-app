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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Core product information
            $table->string('sku')->unique()->comment('Stock Keeping Unit - unique product identifier');
            $table->string('name')->comment('Product name/title');
            $table->text('description')->nullable()->comment('Detailed product description');

            // Category relationship
            $table->foreignId('category_id')->constrained()->cascadeOnDelete()->comment('Product category');

            // Product status and configuration
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active')->comment('Product availability status');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Base unit price');
            $table->string('unit_type', 50)->default('piece')->comment('Unit of measurement (piece, kg, liter, etc.)');

            // Inventory management
            $table->integer('reorder_level')->nullable()->comment('Stock level that triggers reorder alert');
            $table->decimal('tax_rate', 5, 4)->default(0.0000)->comment('Tax rate as decimal (e.g., 0.18 for 18%)');

            // Physical attributes
            $table->decimal('weight', 8, 3)->nullable()->comment('Product weight in kg');
            $table->json('dimensions')->nullable()->comment('Product dimensions (length, width, height)');
            $table->string('color')->nullable()->comment('Product color');
            $table->string('brand')->nullable()->comment('Product brand/manufacturer');

            // Additional metadata
            $table->json('attributes')->nullable()->comment('Additional product attributes (size, material, etc.)');
            $table->string('barcode')->nullable()->comment('Product barcode/QR code');
            $table->string('image_url')->nullable()->comment('Main product image URL');
            $table->json('gallery')->nullable()->comment('Additional product images');

            // SEO and search
            $table->string('meta_title')->nullable()->comment('SEO meta title');
            $table->text('meta_description')->nullable()->comment('SEO meta description');
            $table->json('tags')->nullable()->comment('Product tags for search and filtering');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who created this product');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who last updated this product');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'category_id'], 'products_status_category_index');
            $table->index(['created_at'], 'products_created_at_index');
            $table->index(['sku'], 'products_sku_index');

            // Full-text search indexes (MySQL specific)
            if (config('database.default') !== 'sqlite') {
                $table->fullText(['name', 'description'], 'products_search_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
