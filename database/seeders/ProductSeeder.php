<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating products and inventory...');

        // Get all active categories
        $categories = Category::where('is_active', true)->get();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Please run CategorySeeder first.');

            return;
        }

        // Create products for each category with realistic distribution
        $totalProducts = 0;

        foreach ($categories as $category) {
            $productCount = $this->getProductCountForCategory($category);

            if ($productCount > 0) {
                $this->command->info("Creating {$productCount} products for category: {$category->name}");

                // Create a mix of product types for this category
                $products = $this->createProductsForCategory($category, $productCount);
                $totalProducts += count($products);

                // Create inventory for each product
                $this->createInventoryForProducts($products);
            }
        }

        $this->command->info("Created {$totalProducts} products with inventory across ".$categories->count().' categories.');
    }

    /**
     * Determine how many products to create for each category
     */
    private function getProductCountForCategory(Category $category): int
    {
        // Define product counts based on category type and hierarchy
        $productCounts = [
            // Main categories get more products
            'uniforms' => 12,
            'textbooks' => 15,
            'stationery' => 20,
            'sports-equipment' => 10,
            'technology' => 8,
            'school-bags' => 6,

            // Subcategories get fewer products
            'boys-uniforms' => 6,
            'girls-uniforms' => 6,
            'writing-materials' => 8,
            'math-instruments' => 5,
            'notebooks' => 4,
            'art-supplies' => 6,

            // Subject categories
            'mathematics' => 4,
            'science' => 4,
            'english' => 4,
            'history' => 3,
            'geography' => 3,
            'languages' => 3,

            // Specific item categories
            'boys-shirts' => 3,
            'boys-pants' => 3,
            'boys-shoes' => 2,
            'girls-blouses' => 3,
            'girls-skirts' => 3,
            'girls-shoes' => 2,
            'pens' => 4,
            'pencils' => 4,
            'erasers' => 3,
            'markers' => 3,

            // Sports subcategories
            'team-sports' => 4,
            'individual-sports' => 4,
            'fitness-equipment' => 3,
            'sports-apparel' => 4,
        ];

        return $productCounts[$category->slug] ?? 2; // Default 2 products for unlisted categories
    }

    /**
     * Create products for a specific category
     */
    private function createProductsForCategory(Category $category, int $count): array
    {
        $products = [];

        // Calculate distribution of product types
        $featuredCount = max(1, intval($count * 0.2)); // 20% featured
        $expensiveCount = max(1, intval($count * 0.15)); // 15% expensive
        $budgetCount = max(1, intval($count * 0.25)); // 25% budget
        $digitalCount = $this->shouldHaveDigitalProducts($category) ? max(1, intval($count * 0.1)) : 0; // 10% digital if applicable

        // Create featured products
        for ($i = 0; $i < $featuredCount; $i++) {
            $products[] = Product::factory()
                ->featured()
                ->forCategory($category->id)
                ->create();
        }

        // Create expensive products
        for ($i = 0; $i < $expensiveCount; $i++) {
            $products[] = Product::factory()
                ->expensive()
                ->active()
                ->forCategory($category->id)
                ->create();
        }

        // Create budget products
        for ($i = 0; $i < $budgetCount; $i++) {
            $products[] = Product::factory()
                ->budget()
                ->active()
                ->forCategory($category->id)
                ->create();
        }

        // Create digital products (if applicable)
        for ($i = 0; $i < $digitalCount; $i++) {
            $products[] = Product::factory()
                ->digital()
                ->active()
                ->forCategory($category->id)
                ->create();
        }

        // Fill remaining with regular products
        $remaining = $count - count($products);
        for ($i = 0; $i < $remaining; $i++) {
            $products[] = Product::factory()
                ->active()
                ->forCategory($category->id)
                ->create();
        }

        return $products;
    }

    /**
     * Create inventory records for products with realistic stock levels
     */
    private function createInventoryForProducts(array $products): void
    {
        foreach ($products as $product) {
            // Skip digital products - they don't need physical inventory
            $attributes = $product->attributes ?? [];
            if (isset($attributes['is_digital']) && $attributes['is_digital']) {
                continue;
            }

            // Determine inventory state based on realistic distribution
            $inventoryState = $this->getInventoryState();

            // Create inventory record
            ProductInventory::factory()
                ->{$inventoryState}()
                ->forProduct($product->id)
                ->create();
        }
    }

    /**
     * Get random inventory state with realistic distribution
     */
    private function getInventoryState(): string
    {
        $rand = rand(1, 100);

        if ($rand <= 60) {
            return 'inStock';      // 60% healthy stock
        } elseif ($rand <= 80) {
            return 'lowStock';     // 20% low stock
        } elseif ($rand <= 90) {
            return 'needsReorder'; // 10% needs reorder
        } elseif ($rand <= 95) {
            return 'outOfStock';   // 5% out of stock
        } else {
            return 'overstock';    // 5% overstock
        }
    }

    /**
     * Determine if a category should have digital products
     */
    private function shouldHaveDigitalProducts(Category $category): bool
    {
        $digitalCategories = [
            'textbooks',
            'mathematics',
            'science',
            'english',
            'history',
            'geography',
            'languages',
            'technology',
        ];

        return in_array($category->slug, $digitalCategories);
    }
}
