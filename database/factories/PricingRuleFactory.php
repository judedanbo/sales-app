<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PricingRule>
 */
class PricingRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'bulk_discount', 'customer_tier', 'time_based', 'quantity_break',
            'bundle', 'loyalty', 'geographic', 'competitor_match', 'dynamic',
        ];

        $type = $this->faker->randomElement($types);
        $discountType = $this->faker->randomElement(['percentage', 'fixed_amount', 'fixed_price']);

        $discountValue = match ($discountType) {
            'percentage' => $this->faker->randomFloat(2, 5, 50), // 5-50%
            'fixed_amount' => $this->faker->randomFloat(2, 5, 100), // GHS 5-100
            'fixed_price' => $this->faker->randomFloat(2, 10, 200), // GHS 10-200
        };

        return [
            'name' => $this->generateRuleName($type),
            'type' => $type,
            'conditions' => $this->generateConditions($type),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'minimum_quantity' => $this->faker->boolean(60) ? $this->faker->numberBetween(1, 10) : null,
            'maximum_quantity' => $this->faker->boolean(30) ? $this->faker->numberBetween(20, 100) : null,
            'minimum_amount' => $this->faker->boolean(70) ? $this->faker->randomFloat(2, 50, 500) : null,
            'maximum_amount' => $this->faker->boolean(20) ? $this->faker->randomFloat(2, 1000, 5000) : null,
            'applies_to' => $this->faker->randomElement(['all_products', 'specific_products', 'categories', 'variants']),
            'applicable_product_ids' => $this->faker->boolean(30) ? $this->faker->randomElements([1, 2, 3, 4, 5], 2) : null,
            'applicable_category_ids' => $this->faker->boolean(40) ? $this->faker->randomElements([1, 2, 3], 1) : null,
            'customer_scope' => $this->faker->randomElement(['all_customers', 'customer_groups', 'specific_customers']),
            'customer_group_ids' => $this->faker->boolean(30) ? ['vip', 'premium'] : null,
            'valid_from' => $this->faker->boolean(80) ? $this->faker->dateTimeBetween('-1 month', '+1 month') : null,
            'valid_to' => $this->faker->boolean(70) ? $this->faker->dateTimeBetween('+1 month', '+6 months') : null,
            'time_constraints' => $this->generateTimeConstraints($type),
            'location_constraints' => $this->faker->boolean(20) ? ['Ghana', 'Accra'] : null,
            'priority' => $this->faker->numberBetween(1, 100),
            'usage_limit' => $this->faker->boolean(50) ? $this->faker->numberBetween(50, 1000) : null,
            'usage_count' => 0,
            'per_customer_limit' => $this->faker->boolean(40) ? $this->faker->numberBetween(1, 5) : null,
            'is_active' => $this->faker->boolean(85),
            'stackable' => $this->faker->boolean(30),
            'description' => $this->faker->sentence(),
            'metadata' => $this->generateMetadata($type),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate rule name based on type
     */
    private function generateRuleName(string $type): string
    {
        $names = [
            'bulk_discount' => ['Volume Discount', 'Bulk Purchase Savings', 'Wholesale Pricing'],
            'customer_tier' => ['VIP Customer Discount', 'Premium Member Pricing', 'Loyalty Tier Pricing'],
            'time_based' => ['Happy Hour Pricing', 'Weekend Special', 'Holiday Sale'],
            'quantity_break' => ['Quantity Break Pricing', 'Buy More Save More', 'Quantity Discount'],
            'bundle' => ['Bundle Deal', 'Combo Pricing', 'Package Discount'],
            'loyalty' => ['Loyalty Reward', 'Member Benefits', 'Repeat Customer Discount'],
            'geographic' => ['Local Discount', 'Regional Pricing', 'City Special'],
            'competitor_match' => ['Price Match', 'Competitive Pricing', 'Beat the Competition'],
            'dynamic' => ['Smart Pricing', 'Dynamic Discount', 'AI Optimized Pricing'],
        ];

        return $this->faker->randomElement($names[$type]).' - '.$this->faker->word();
    }

    /**
     * Generate conditions based on rule type
     */
    private function generateConditions(string $type): array
    {
        return match ($type) {
            'bulk_discount' => [
                'min_quantity' => $this->faker->numberBetween(10, 50),
                'category_ids' => [1, 2],
            ],
            'customer_tier' => [
                'customer_groups' => ['vip', 'premium'],
                'membership_duration' => '6_months',
            ],
            'time_based' => [
                'days_of_week' => ['friday', 'saturday', 'sunday'],
                'hours' => ['14:00-18:00'],
            ],
            'quantity_break' => [
                'breaks' => [
                    ['min_qty' => 5, 'discount' => 10],
                    ['min_qty' => 10, 'discount' => 15],
                    ['min_qty' => 20, 'discount' => 20],
                ],
            ],
            'bundle' => [
                'required_products' => [1, 2, 3],
                'bundle_size' => $this->faker->numberBetween(2, 5),
            ],
            'loyalty' => [
                'points_required' => $this->faker->numberBetween(100, 1000),
                'membership_level' => 'gold',
            ],
            'geographic' => [
                'regions' => ['Greater Accra', 'Ashanti'],
                'postal_codes' => ['GA', 'AS'],
            ],
            'competitor_match' => [
                'competitors' => ['Competitor A', 'Competitor B'],
                'match_percentage' => 100,
            ],
            'dynamic' => [
                'algorithm' => 'demand_based',
                'factors' => ['inventory_level', 'demand', 'competition'],
            ],
        };
    }

    /**
     * Generate time constraints
     */
    private function generateTimeConstraints(?string $type): ?array
    {
        if (! in_array($type, ['time_based', 'dynamic'])) {
            return null;
        }

        return [
            'days_of_week' => $this->faker->randomElements(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], 3),
            'hours' => [
                'start' => $this->faker->time('H:i'),
                'end' => $this->faker->time('H:i'),
            ],
            'timezone' => 'Africa/Accra',
        ];
    }

    /**
     * Generate metadata
     */
    private function generateMetadata(string $type): array
    {
        return [
            'created_by_system' => false,
            'auto_expire' => $this->faker->boolean(30),
            'notification_sent' => false,
            'performance_metrics' => [
                'usage_rate' => 0,
                'savings_generated' => 0,
            ],
        ];
    }

    /**
     * Create a bulk discount rule
     */
    public function bulkDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bulk_discount',
            'name' => 'Bulk Purchase Discount - '.$this->faker->word(),
            'discount_type' => 'percentage',
            'discount_value' => $this->faker->randomFloat(2, 10, 25),
            'minimum_quantity' => $this->faker->numberBetween(10, 50),
            'conditions' => [
                'min_quantity' => $this->faker->numberBetween(10, 50),
                'applies_to_all' => true,
            ],
        ]);
    }

    /**
     * Create a customer tier rule
     */
    public function customerTier(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer_tier',
            'name' => 'VIP Customer Discount - '.$this->faker->word(),
            'discount_type' => 'percentage',
            'discount_value' => $this->faker->randomFloat(2, 5, 20),
            'customer_scope' => 'customer_groups',
            'customer_group_ids' => ['vip', 'premium'],
        ]);
    }

    /**
     * Create a time-based rule
     */
    public function timeBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'time_based',
            'name' => 'Happy Hour Special - '.$this->faker->word(),
            'discount_type' => 'percentage',
            'discount_value' => $this->faker->randomFloat(2, 15, 30),
            'time_constraints' => [
                'days_of_week' => ['friday', 'saturday'],
                'hours' => ['start' => '16:00', 'end' => '19:00'],
            ],
        ]);
    }

    /**
     * Create an active rule
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'valid_from' => now()->subDays(7),
            'valid_to' => now()->addMonths(3),
        ]);
    }

    /**
     * Create an inactive rule
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an expired rule
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'valid_from' => now()->subMonths(6),
            'valid_to' => now()->subDays(7),
        ]);
    }
}
