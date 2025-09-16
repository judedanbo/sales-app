<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'initial_stock', 'purchase', 'sale', 'return_from_customer',
            'return_to_supplier', 'adjustment', 'transfer_in', 'transfer_out',
            'damaged', 'expired', 'theft', 'manufacturing', 'reservation', 'release_reservation',
        ];

        $type = $this->faker->randomElement($types);

        // Determine quantity change based on type
        $inboundTypes = ['initial_stock', 'purchase', 'return_from_customer', 'transfer_in', 'manufacturing', 'release_reservation'];
        $isInbound = in_array($type, $inboundTypes);

        $quantityChange = $isInbound
            ? $this->faker->numberBetween(1, 100)
            : -$this->faker->numberBetween(1, 50);

        $quantityBefore = $this->faker->numberBetween(0, 200);
        $quantityAfter = max(0, $quantityBefore + $quantityChange);

        $unitCost = $this->faker->randomFloat(2, 1, 100);

        return [
            'type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $this->faker->boolean(70) ? $unitCost : null,
            'total_cost' => $this->faker->boolean(70) ? abs($quantityChange) * $unitCost : null,
            'currency' => 'GHS',
            'reference_type' => $this->faker->randomElement(['order', 'invoice', 'receipt', 'adjustment', null]),
            'reference_id' => $this->faker->boolean(60) ? $this->faker->uuid : null,
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence : null,
            'location' => $this->faker->randomElement(['Main Warehouse', 'Store A', 'Store B', 'Online', null]),
            'batch_number' => $this->faker->boolean(30) ? 'BATCH-'.$this->faker->randomNumber(6) : null,
            'expiry_date' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d') : null,
            'movement_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'is_confirmed' => $this->faker->boolean(95),
            'confirmed_at' => $this->faker->boolean(95) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'user_id' => 1, // Will be set when creating
        ];
    }

    /**
     * Create a purchase movement
     */
    public function purchase(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(10, 100);
            $unitCost = $this->faker->randomFloat(2, 5, 50);

            return [
                'type' => 'purchase',
                'quantity_change' => $quantity,
                'quantity_after' => $attributes['quantity_before'] + $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'reference_type' => 'purchase_order',
                'reference_id' => 'PO-'.$this->faker->randomNumber(6),
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ];
        });
    }

    /**
     * Create a sale movement
     */
    public function sale(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(1, 20);
            $unitCost = $this->faker->randomFloat(2, 5, 50);

            return [
                'type' => 'sale',
                'quantity_change' => -$quantity,
                'quantity_after' => max(0, $attributes['quantity_before'] - $quantity),
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'reference_type' => 'order',
                'reference_id' => 'ORD-'.$this->faker->randomNumber(6),
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ];
        });
    }

    /**
     * Create an adjustment movement
     */
    public function adjustment(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->randomElement([-10, -5, -3, -2, -1, 1, 2, 3, 5, 10]);

            return [
                'type' => 'adjustment',
                'quantity_change' => $quantity,
                'quantity_after' => max(0, $attributes['quantity_before'] + $quantity),
                'reference_type' => 'stock_count',
                'notes' => $quantity > 0 ? 'Stock count found additional items' : 'Stock count found missing items',
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ];
        });
    }

    /**
     * Create an initial stock movement
     */
    public function initialStock(): static
    {
        return $this->state(function (array $attributes) {
            $quantity = $this->faker->numberBetween(20, 200);
            $unitCost = $this->faker->randomFloat(2, 10, 100);

            return [
                'type' => 'initial_stock',
                'quantity_change' => $quantity,
                'quantity_before' => 0,
                'quantity_after' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'notes' => 'Initial inventory setup',
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ];
        });
    }

    /**
     * Mark movement as confirmed
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark movement as unconfirmed
     */
    public function unconfirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_confirmed' => false,
            'confirmed_at' => null,
        ]);
    }
}
