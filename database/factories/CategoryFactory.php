<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(10),
            'parent_id' => null, // Will be set when creating child categories
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'color' => $this->faker->safeColorName(),
            'icon' => $this->faker->randomElement([
                'package',
                'shirt',
                'book',
                'pencil',
                'palette',
                'trophy',
                'calculator',
                'briefcase',
                'folder',
                'star',
            ]),
            'created_by' => User::inRandomOrder()->first()?->id,
            'updated_by' => User::inRandomOrder()->first()?->id,
        ];
    }

    /**
     * Create a root category (no parent).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Create a child category with a specific parent.
     */
    public function child(?Category $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parentCategory = $parent ?? Category::roots()->inRandomOrder()->first();

            return [
                'parent_id' => $parentCategory?->id,
                'sort_order' => $this->faker->numberBetween(0, 10),
            ];
        });
    }

    /**
     * Create an inactive category.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a school uniforms category.
     */
    public function uniforms(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'School Uniforms',
            'slug' => 'school-uniforms',
            'description' => 'Complete range of school uniforms including shirts, pants, skirts, and accessories',
            'color' => 'blue',
            'icon' => 'shirt',
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a textbooks category.
     */
    public function textbooks(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Textbooks',
            'slug' => 'textbooks',
            'description' => 'Academic textbooks for all grades and subjects',
            'color' => 'green',
            'icon' => 'book',
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a stationery category.
     */
    public function stationery(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Stationery',
            'slug' => 'stationery',
            'description' => 'Writing materials, notebooks, and office supplies',
            'color' => 'yellow',
            'icon' => 'pencil',
            'sort_order' => 3,
        ]);
    }

    /**
     * Create a sports equipment category.
     */
    public function sportsEquipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sports Equipment',
            'slug' => 'sports-equipment',
            'description' => 'Sports gear, equipment, and athletic wear',
            'color' => 'red',
            'icon' => 'trophy',
            'sort_order' => 4,
        ]);
    }

    /**
     * Create a boys uniform subcategory.
     */
    public function boysUniforms(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Boys Uniforms',
            'slug' => 'boys-uniforms',
            'description' => 'Uniform items specifically for male students',
            'parent_id' => $parent->id,
            'color' => 'blue',
            'icon' => 'shirt',
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a girls uniform subcategory.
     */
    public function girlsUniforms(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Girls Uniforms',
            'slug' => 'girls-uniforms',
            'description' => 'Uniform items specifically for female students',
            'parent_id' => $parent->id,
            'color' => 'pink',
            'icon' => 'shirt',
            'sort_order' => 2,
        ]);
    }

    /**
     * Create a mathematical instruments category.
     */
    public function mathInstruments(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Mathematical Instruments',
            'slug' => 'mathematical-instruments',
            'description' => 'Geometry sets, calculators, and mathematical tools',
            'parent_id' => $parent->id,
            'color' => 'orange',
            'icon' => 'calculator',
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a writing materials category.
     */
    public function writingMaterials(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Writing Materials',
            'slug' => 'writing-materials',
            'description' => 'Pens, pencils, erasers, and writing accessories',
            'parent_id' => $parent->id,
            'color' => 'black',
            'icon' => 'pencil',
            'sort_order' => 2,
        ]);
    }

    /**
     * Create category with specific sort order.
     */
    public function withSortOrder(int $sortOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Create category with specific color and icon.
     */
    public function withAppearance(string $color, string $icon): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
            'icon' => $icon,
        ]);
    }
}
