<?php

use App\Models\User;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\StockMovement;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access inventory index route with authentication and permissions', function () {
    // Create user with inventory permissions
    $user = User::factory()->create();
    $user->givePermissionTo(['view_inventory', 'view_stock_movements']);

    // Create test data manually (factory seems to have issues)
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

    // Act
    $response = $this->actingAs($user)->get('/inventory');

    // Assert
    $response->assertOk();
    $response->assertInertia(function ($assert) {
        $assert->component('Inventory/Index')
            ->has('statistics')
            ->has('movements')
            ->has('lowStockProducts')
            ->has('filters');
    });
});

it('requires authentication to access inventory', function () {
    $response = $this->get('/inventory');

    $response->assertRedirect('/login');
});

it('requires view_inventory permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/inventory');

    $response->assertForbidden();
});
