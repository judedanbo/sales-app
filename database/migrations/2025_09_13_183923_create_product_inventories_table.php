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
        Schema::create('product_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity_on_hand')->default(0)->comment('Current stock quantity');
            $table->integer('quantity_available')->default(0)->comment('Available quantity (on_hand - reserved)');
            $table->integer('quantity_reserved')->default(0)->comment('Reserved quantity for orders');
            $table->integer('minimum_stock_level')->nullable()->comment('Minimum stock threshold');
            $table->integer('maximum_stock_level')->nullable()->comment('Maximum stock capacity');
            $table->integer('reorder_point')->nullable()->comment('Stock level to trigger reorder');
            $table->integer('reorder_quantity')->nullable()->comment('Quantity to reorder');
            $table->timestamp('last_stock_count')->nullable()->comment('Date of last physical count');
            $table->timestamp('last_movement_at')->nullable()->comment('Date of last inventory movement');
            $table->timestamps();

            $table->unique('product_id');
            $table->index(['quantity_on_hand', 'minimum_stock_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_inventories');
    }
};
