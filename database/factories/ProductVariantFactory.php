<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '28', '30', '32', '34', '36', '38', '40'];
        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Yellow', 'Purple', 'Orange', 'Pink', 'Gray'];
        $materials = ['Cotton', 'Polyester', 'Wool', 'Silk', 'Denim', 'Leather', 'Linen', 'Nylon'];

        $size = $this->faker->randomElement($sizes);
        $color = $this->faker->randomElement($colors);
        $material = $this->faker->randomElement($materials);

        return [
            'name' => $size.' '.$color,
            'size' => $this->faker->boolean(70) ? $size : null,
            'color' => $this->faker->boolean(80) ? $color : null,
            'material' => $this->faker->boolean(50) ? $material : null,
            'unit_price' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 10, 500) : null,
            'cost_price' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 5, 250) : null,
            'weight' => $this->faker->boolean(60) ? $this->faker->randomFloat(3, 0.1, 10) : null,
            'dimensions' => $this->faker->boolean(40) ? [
                'length' => $this->faker->numberBetween(5, 100),
                'width' => $this->faker->numberBetween(5, 100),
                'height' => $this->faker->numberBetween(1, 50),
            ] : null,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'is_default' => false, // Will be set programmatically
            'sort_order' => $this->faker->numberBetween(1, 10),
            'barcode' => $this->faker->boolean(40) ? $this->faker->ean13() : null,
            'attributes' => $this->faker->boolean(20) ? [
                'feature_1' => $this->faker->word,
                'feature_2' => $this->faker->word,
            ] : null,
        ];
    }

    /**
     * Mark this variant as the default variant
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    /**
     * Create a variant with specific size
     */
    public function withSize(string $size): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => $size,
            'name' => $size.' '.($attributes['color'] ?? 'Variant'),
        ]);
    }

    /**
     * Create a variant with specific color
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
            'name' => ($attributes['size'] ?? 'One Size').' '.$color,
        ]);
    }

    /**
     * Create a variant with specific material
     */
    public function withMaterial(string $material): static
    {
        return $this->state(fn (array $attributes) => [
            'material' => $material,
        ]);
    }

    /**
     * Create an active variant
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create an inactive variant
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
