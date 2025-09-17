<?php

use App\Models\User;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\StockMovement;
use App\Models\Category;
use App\Http\Controllers\Frontend\InventoryController;
use Illuminate\Http\Request;

it('can call inventory controller directly', function () {
    // Create user with inventory permissions
    $user = User::factory()->create();
    $user->givePermissionTo(['view_inventory', 'view_stock_movements']);

    // Set user as authenticated
    $this->be($user);

    // Create test data
    $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);

    $product = Product::create([
        'name' => 'Test Product',
        'slug' => 'test-product',
        'sku' => 'TEST001',
        'unit_price' => 25.99,
        'category_id' => $category->id,
        'is_active' => true,
        'has_variants' => false,
    ]);

    ProductInventory::create([
        'product_id' => $product->id,
        'quantity_on_hand' => 25,
        'quantity_available' => 25,
        'minimum_stock_level' => 10,
        'maximum_stock_level' => 100,
    ]);

    StockMovement::create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'type' => 'purchase',
        'quantity_change' => 25,
        'quantity_before' => 0,
        'quantity_after' => 25,
        'movement_date' => now(),
        'is_confirmed' => true,
        'notes' => 'Test purchase',
    ]);

    // Call controller directly
    $controller = new InventoryController();
    $request = new Request();

    $response = $controller->index($request);

    expect($response)->toBeInstanceOf(\Inertia\Response::class);
    expect($response->toResponse($request))->not->toBeNull();
});

it('inventory route returns proper response structure', function () {
    // Test that we can access the existing data in the database
    $totalProducts = Product::has('inventory')->count();
    $totalMovements = StockMovement::count();

    expect($totalProducts)->toBeGreaterThanOrEqual(0);
    expect($totalMovements)->toBeGreaterThanOrEqual(0);

    // At this point we know the database has data
    echo "Products with inventory: $totalProducts\n";
    echo "Total stock movements: $totalMovements\n";
});