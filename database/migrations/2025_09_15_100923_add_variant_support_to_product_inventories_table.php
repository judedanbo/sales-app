<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create the product_variants table (should already be done)
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('sku')->unique()->comment('Variant-specific SKU');
                $table->string('name')->comment('Variant name (e.g., "Large Red")');
                $table->string('size')->nullable()->comment('Size variation (S, M, L, etc.)');
                $table->string('color')->nullable()->comment('Color variation');
                $table->string('material')->nullable()->comment('Material variation');
                $table->json('attributes')->nullable()->comment('Additional custom attributes');
                $table->decimal('unit_price', 10, 2)->nullable()->comment('Override price for this variant');
                $table->decimal('cost_price', 10, 2)->nullable()->comment('Cost price for this variant');
                $table->decimal('weight', 8, 3)->nullable()->comment('Weight in kg');
                $table->json('dimensions')->nullable()->comment('Length, width, height in cm');
                $table->string('image_url')->nullable()->comment('Primary variant image');
                $table->json('gallery')->nullable()->comment('Additional variant images');
                $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
                $table->boolean('is_default')->default(false)->comment('Is this the default variant');
                $table->integer('sort_order')->default(0)->comment('Display order');
                $table->string('barcode')->nullable()->comment('Variant-specific barcode');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'status']);
                $table->index(['product_id', 'is_default']);
                $table->index(['sku']);
                $table->index(['size', 'color', 'material']);
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Add the variant column to product_inventories if it doesn't exist
        if (! Schema::hasColumn('product_inventories', 'product_variant_id')) {
            Schema::table('product_inventories', function (Blueprint $table) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id');
                $table->index(['product_variant_id']);
            });
        }

        // Drop the old unique constraint and add the new one if needed
        try {
            DB::statement('ALTER TABLE product_inventories DROP INDEX product_inventories_product_id_unique');
        } catch (\Exception $e) {
            // Constraint might already be dropped
        }

        // Add new constraints if they don't exist
        $uniqueExists = DB::select("SHOW INDEX FROM product_inventories WHERE Key_name = 'product_inventories_product_id_product_variant_id_unique'");
        if (empty($uniqueExists)) {
            Schema::table('product_inventories', function (Blueprint $table) {
                $table->unique(['product_id', 'product_variant_id']);
            });
        }

        $foreignKeyExists = DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'product_inventories' AND CONSTRAINT_NAME = 'product_inventories_product_variant_id_foreign'");
        if (empty($foreignKeyExists)) {
            Schema::table('product_inventories', function (Blueprint $table) {
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_inventories', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['product_id', 'product_variant_id']);

            // Drop variant foreign key and column
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');

            // Restore original unique constraint
            $table->unique('product_id');
        });
    }
};
