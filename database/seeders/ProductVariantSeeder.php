<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first 5 products that don't have variants yet
        $products = Product::whereDoesntHave('variants')->limit(5)->get();

        foreach ($products as $product) {
            // Remove existing inventory without variant to make room for variant inventories
            ProductInventory::where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->delete();

            // Create 2-4 variants per product
            $variantCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $variantCount; $i++) {
                // Create variant
                $variant = ProductVariant::factory()
                    ->for($product)
                    ->create([
                        'is_default' => $i === 0, // First variant is default
                        'created_by' => 1, // Admin user
                        'updated_by' => 1,
                    ]);

                // Create inventory for this variant
                ProductInventory::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity_on_hand' => fake()->numberBetween(5, 200),
                    'quantity_available' => fake()->numberBetween(5, 200),
                    'quantity_reserved' => fake()->numberBetween(0, 10),
                    'minimum_stock_level' => fake()->numberBetween(5, 20),
                    'maximum_stock_level' => fake()->numberBetween(100, 500),
                    'reorder_point' => fake()->numberBetween(10, 30),
                    'reorder_quantity' => fake()->numberBetween(50, 100),
                    'last_stock_count' => fake()->dateTimeBetween('-1 month', 'now'),
                    'last_movement_at' => fake()->dateTimeBetween('-1 week', 'now'),
                ]);
            }
        }

        // Create some products with clothing variants (specific combinations)
        $clothingProducts = Product::whereHas('category', function ($q) {
            $q->where('name', 'like', '%cloth%')
                ->orWhere('name', 'like', '%apparel%')
                ->orWhere('name', 'like', '%fashion%');
        })->whereDoesntHave('variants')->limit(3)->get();

        foreach ($clothingProducts as $product) {
            // Remove existing inventory without variant to make room for variant inventories
            ProductInventory::where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->delete();

            $sizes = ['S', 'M', 'L', 'XL'];
            $colors = ['Black', 'White', 'Red', 'Blue'];

            $isFirst = true;
            foreach ($sizes as $size) {
                foreach (fake()->randomElements($colors, 2) as $color) {
                    $variant = ProductVariant::factory()
                        ->for($product)
                        ->withSize($size)
                        ->withColor($color)
                        ->create([
                            'is_default' => $isFirst,
                            'created_by' => 1,
                            'updated_by' => 1,
                            'unit_price' => fake()->randomFloat(2, 20, 150), // Override price for variants
                        ]);

                    $isFirst = false;

                    // Create inventory for this variant
                    ProductInventory::create([
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'quantity_on_hand' => fake()->numberBetween(10, 100),
                        'quantity_available' => fake()->numberBetween(8, 95),
                        'quantity_reserved' => fake()->numberBetween(0, 5),
                        'minimum_stock_level' => fake()->numberBetween(5, 15),
                        'maximum_stock_level' => fake()->numberBetween(80, 200),
                        'reorder_point' => fake()->numberBetween(10, 25),
                        'reorder_quantity' => fake()->numberBetween(30, 80),
                        'last_stock_count' => fake()->dateTimeBetween('-1 month', 'now'),
                        'last_movement_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    ]);
                }
            }
        }

        $this->command->info('Product variants seeded successfully!');
    }
}
