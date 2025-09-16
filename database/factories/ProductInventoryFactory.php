<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductInventory>
 */
class ProductInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityOnHand = $this->faker->numberBetween(0, 500);
        $quantityReserved = $this->faker->numberBetween(0, min(50, $quantityOnHand));
        $quantityAvailable = max(0, $quantityOnHand - $quantityReserved);

        $minimumStockLevel = $this->faker->numberBetween(5, 50);
        $maximumStockLevel = $this->faker->numberBetween($minimumStockLevel + 50, 1000);
        $reorderPoint = $this->faker->numberBetween($minimumStockLevel, $minimumStockLevel + 20);
        $reorderQuantity = $this->faker->numberBetween(50, 200);

        return [
            'quantity_on_hand' => $quantityOnHand,
            'quantity_available' => $quantityAvailable,
            'quantity_reserved' => $quantityReserved,
            'minimum_stock_level' => $minimumStockLevel,
            'maximum_stock_level' => $maximumStockLevel,
            'reorder_point' => $reorderPoint,
            'reorder_quantity' => $reorderQuantity,
            'last_stock_count' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'last_movement_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Create inventory with healthy stock levels
     */
    public function inStock(): static
    {
        return $this->state(function (array $attributes) {
            $quantityOnHand = $this->faker->numberBetween(100, 500);
            $quantityReserved = $this->faker->numberBetween(0, 20);

            return [
                'quantity_on_hand' => $quantityOnHand,
                'quantity_available' => max(0, $quantityOnHand - $quantityReserved),
                'quantity_reserved' => $quantityReserved,
                'minimum_stock_level' => $this->faker->numberBetween(10, 30),
            ];
        });
    }

    /**
     * Create inventory with low stock levels
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $minimumStockLevel = $this->faker->numberBetween(10, 30);
            $quantityOnHand = $this->faker->numberBetween(1, $minimumStockLevel);
            $quantityReserved = $this->faker->numberBetween(0, min(5, $quantityOnHand));

            return [
                'quantity_on_hand' => $quantityOnHand,
                'quantity_available' => max(0, $quantityOnHand - $quantityReserved),
                'quantity_reserved' => $quantityReserved,
                'minimum_stock_level' => $minimumStockLevel,
            ];
        });
    }

    /**
     * Create inventory that's out of stock
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => 0,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'minimum_stock_level' => $this->faker->numberBetween(5, 20),
        ]);
    }

    /**
     * Create inventory with high stock levels (overstock)
     */
    public function overstock(): static
    {
        return $this->state(function (array $attributes) {
            $maximumStockLevel = $this->faker->numberBetween(200, 500);
            $quantityOnHand = $this->faker->numberBetween($maximumStockLevel + 50, $maximumStockLevel + 200);
            $quantityReserved = $this->faker->numberBetween(0, 50);

            return [
                'quantity_on_hand' => $quantityOnHand,
                'quantity_available' => max(0, $quantityOnHand - $quantityReserved),
                'quantity_reserved' => $quantityReserved,
                'maximum_stock_level' => $maximumStockLevel,
            ];
        });
    }

    /**
     * Create inventory that needs reordering
     */
    public function needsReorder(): static
    {
        return $this->state(function (array $attributes) {
            $reorderPoint = $this->faker->numberBetween(20, 50);
            $quantityOnHand = $this->faker->numberBetween(1, $reorderPoint);
            $quantityReserved = $this->faker->numberBetween(0, min(10, $quantityOnHand));

            return [
                'quantity_on_hand' => $quantityOnHand,
                'quantity_available' => max(0, $quantityOnHand - $quantityReserved),
                'quantity_reserved' => $quantityReserved,
                'reorder_point' => $reorderPoint,
                'minimum_stock_level' => $this->faker->numberBetween(5, $reorderPoint - 5),
            ];
        });
    }

    /**
     * Create inventory with heavy reservations
     */
    public function heavyReservations(): static
    {
        return $this->state(function (array $attributes) {
            $quantityOnHand = $this->faker->numberBetween(50, 200);
            $quantityReserved = $this->faker->numberBetween(30, min(80, $quantityOnHand));

            return [
                'quantity_on_hand' => $quantityOnHand,
                'quantity_available' => max(0, $quantityOnHand - $quantityReserved),
                'quantity_reserved' => $quantityReserved,
            ];
        });
    }

    /**
     * Create inventory for a specific product
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Create inventory for a specific product variant
     */
    public function forVariant(int $productId, int $variantId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ]);
    }
}
