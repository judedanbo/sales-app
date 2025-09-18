<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productTypes = [
            'Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports',
            'Beauty', 'Toys', 'Automotive', 'Health', 'Food & Beverage',
        ];

        $productNames = [
            'Electronics' => ['Smartphone', 'Laptop', 'Tablet', 'Headphones', 'Camera', 'Smart Watch', 'Speaker'],
            'Clothing' => ['T-Shirt', 'Jeans', 'Dress', 'Jacket', 'Shoes', 'Hat', 'Socks'],
            'Books' => ['Novel', 'Textbook', 'Magazine', 'Comic Book', 'Manual', 'Dictionary'],
            'Home & Garden' => ['Sofa', 'Table', 'Lamp', 'Plant Pot', 'Tool Set', 'Curtains'],
            'Sports' => ['Football', 'Basketball', 'Tennis Racket', 'Running Shoes', 'Yoga Mat'],
            'Beauty' => ['Lipstick', 'Foundation', 'Perfume', 'Shampoo', 'Face Cream'],
            'Toys' => ['Action Figure', 'Board Game', 'Puzzle', 'Doll', 'Building Blocks'],
            'Automotive' => ['Car Parts', 'Motor Oil', 'Tire', 'Car Accessories'],
            'Health' => ['Vitamins', 'First Aid Kit', 'Blood Pressure Monitor'],
            'Food & Beverage' => ['Coffee', 'Tea', 'Snacks', 'Beverages', 'Organic Food'],
        ];

        $type = $this->faker->randomElement($productTypes);
        $baseName = $this->faker->randomElement($productNames[$type]);
        $brand = $this->faker->company();
        $name = $brand.' '.$baseName;

        $unitPrice = $this->faker->randomFloat(2, 5, 500);

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('??###??')),
            'name' => $name,
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement(['active', 'inactive', 'discontinued']),
            'unit_price' => $unitPrice,
            'unit_type' => $this->faker->randomElement(['piece', 'kg', 'liter', 'meter', 'pack', 'box', 'set']),
            'reorder_level' => $this->faker->numberBetween(5, 50),
            'tax_rate' => $this->faker->randomElement([0.0000, 0.0500, 0.1250, 0.1800]),
            'weight' => $this->faker->randomFloat(3, 0.1, 20),
            'dimensions' => [
                'length' => $this->faker->numberBetween(5, 100),
                'width' => $this->faker->numberBetween(5, 100),
                'height' => $this->faker->numberBetween(1, 50),
            ],
            'color' => $this->faker->colorName(),
            'brand' => $brand,
            'attributes' => [
                'model' => $this->faker->bothify('??-####'),
                'material' => $this->faker->randomElement(['Plastic', 'Metal', 'Wood', 'Glass', 'Fabric', 'Leather', 'Ceramic']),
                'features' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'specifications' => [
                    'warranty' => $this->faker->randomElement(['1 year', '2 years', '6 months', 'Lifetime']),
                    'origin' => $this->faker->country(),
                    'certification' => $this->faker->randomElement(['CE', 'ISO', 'FDA', 'RoHS', null]),
                ],
                'is_digital' => $this->faker->boolean(10), // 10% chance of digital product
                'is_featured' => $this->faker->boolean(20), // 20% chance of featured
            ],
            'barcode' => $this->faker->ean13(),
            'image_url' => null, // Will be populated later if needed
            'gallery' => [],
            'meta_title' => $name.' | Best Quality Products',
            'meta_description' => $this->faker->sentence(10),
            'tags' => $this->faker->words(3),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Create a featured product
     */
    public function featured(): static
    {
        return $this->state(function (array $attributes) {
            $currentAttributes = $attributes['attributes'] ?? [];
            $currentAttributes['is_featured'] = true;

            return [
                'attributes' => $currentAttributes,
                'status' => 'active',
            ];
        });
    }

    /**
     * Create an active product
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create an inactive product
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a discontinued product
     */
    public function discontinued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'discontinued',
        ]);
    }

    /**
     * Create a digital product
     */
    public function digital(): static
    {
        return $this->state(function (array $attributes) {
            $currentAttributes = $attributes['attributes'] ?? [];
            $currentAttributes['is_digital'] = true;

            return [
                'attributes' => $currentAttributes,
                'weight' => null,
                'dimensions' => null,
                'unit_type' => 'license',
            ];
        });
    }

    /**
     * Create a physical product
     */
    public function physical(): static
    {
        return $this->state(function (array $attributes) {
            $currentAttributes = $attributes['attributes'] ?? [];
            $currentAttributes['is_digital'] = false;

            return [
                'attributes' => $currentAttributes,
            ];
        });
    }

    /**
     * Create an expensive product
     */
    public function expensive(): static
    {
        return $this->state(function (array $attributes) {
            $unitPrice = $this->faker->randomFloat(2, 500, 2000);

            return [
                'unit_price' => $unitPrice,
            ];
        });
    }

    /**
     * Create a budget product
     */
    public function budget(): static
    {
        return $this->state(function (array $attributes) {
            $unitPrice = $this->faker->randomFloat(2, 5, 50);

            return [
                'unit_price' => $unitPrice,
            ];
        });
    }

    /**
     * Create a product for a specific category
     */
    public function forCategory(int $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
