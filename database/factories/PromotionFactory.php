<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'percentage_discount', 'fixed_discount', 'bogo', 'free_shipping',
            'bundle_deal', 'flash_sale', 'loyalty_points', 'referral', 'seasonal', 'clearance',
        ];

        $type = $this->faker->randomElement($types);
        $startsAt = $this->faker->dateTimeBetween('-1 month', '+1 week');
        $endsAt = $this->faker->dateTimeBetween($startsAt, $startsAt->format('Y-m-d H:i:s').' +3 months');

        return [
            'name' => $this->generatePromotionName($type),
            'code' => $this->faker->boolean(70) ? strtoupper($this->faker->bothify('??##??')) : null,
            'type' => $type,
            'discount_value' => $this->generateDiscountValue($type),
            'minimum_purchase' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 50, 500) : null,
            'maximum_discount' => $this->faker->boolean(40) ? $this->faker->randomFloat(2, 50, 200) : null,
            'buy_quantity' => $type === 'bogo' ? $this->faker->numberBetween(1, 3) : null,
            'get_quantity' => $type === 'bogo' ? $this->faker->numberBetween(1, 2) : null,
            'get_discount_percentage' => $type === 'bogo' ? $this->faker->randomFloat(2, 50, 100) : null,
            'applies_to' => $this->faker->randomElement(['all_products', 'specific_products', 'categories', 'variants']),
            'applicable_product_ids' => $this->faker->boolean(30) ? $this->faker->randomElements([1, 2, 3, 4, 5], 2) : null,
            'applicable_category_ids' => $this->faker->boolean(40) ? $this->faker->randomElements([1, 2, 3], 1) : null,
            'excluded_product_ids' => $this->faker->boolean(20) ? $this->faker->randomElements([6, 7, 8], 1) : null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'usage_limit' => $this->faker->boolean(60) ? $this->faker->numberBetween(50, 500) : null,
            'usage_count' => $this->faker->numberBetween(0, 50),
            'per_customer_limit' => $this->faker->boolean(50) ? $this->faker->numberBetween(1, 5) : null,
            'customer_eligibility' => $this->faker->randomElement(['all_customers', 'new_customers', 'returning_customers', 'vip_customers']),
            'customer_groups' => $this->faker->boolean(30) ? ['vip', 'premium'] : null,
            'is_active' => $this->faker->boolean(80),
            'is_public' => $this->faker->boolean(90),
            'stackable' => $this->faker->boolean(25),
            'auto_apply' => $this->faker->boolean(30),
            'description' => $this->generateDescription($type),
            'terms_and_conditions' => $this->generateTermsAndConditions(),
            'banner_image' => $this->faker->boolean(50) ? $this->faker->imageUrl(800, 400, 'business', true, 'promotion') : null,
            'display_rules' => $this->generateDisplayRules($type),
            'utm_campaign' => $this->faker->boolean(40) ? $this->faker->slug : null,
            'utm_source' => $this->faker->boolean(40) ? $this->faker->randomElement(['email', 'social', 'website', 'app']) : null,
            'utm_medium' => $this->faker->boolean(40) ? $this->faker->randomElement(['banner', 'popup', 'newsletter']) : null,
            'analytics_data' => $this->generateAnalyticsData(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /**
     * Generate promotion name based on type
     */
    private function generatePromotionName(string $type): string
    {
        $names = [
            'percentage_discount' => ['Summer Sale', 'Back to School Special', 'Weekend Discount'],
            'fixed_discount' => ['GHS 50 Off', 'Fixed Savings Deal', 'Money Off Special'],
            'bogo' => ['Buy One Get One', 'BOGO Special', 'Double Up Deal'],
            'free_shipping' => ['Free Delivery', 'No Shipping Fees', 'Delivery on Us'],
            'bundle_deal' => ['Bundle & Save', 'Combo Deal', 'Package Special'],
            'flash_sale' => ['Flash Sale', '24 Hour Special', 'Lightning Deal'],
            'loyalty_points' => ['Double Points', 'Bonus Rewards', 'Points Multiplier'],
            'referral' => ['Refer a Friend', 'Referral Bonus', 'Share & Save'],
            'seasonal' => ['Holiday Special', 'Seasonal Sale', 'Festival Deal'],
            'clearance' => ['Clearance Sale', 'Final Markdown', 'Last Chance'],
        ];

        return $this->faker->randomElement($names[$type]).' - '.$this->faker->monthName();
    }

    /**
     * Generate discount value based on type
     */
    private function generateDiscountValue(string $type): ?float
    {
        return match ($type) {
            'percentage_discount' => $this->faker->randomFloat(2, 5, 50),
            'fixed_discount' => $this->faker->randomFloat(2, 10, 100),
            'loyalty_points' => $this->faker->randomFloat(2, 1.5, 3.0), // Multiplier
            'free_shipping', 'bogo', 'bundle_deal' => null,
            default => $this->faker->randomFloat(2, 5, 25),
        };
    }

    /**
     * Generate description
     */
    private function generateDescription(string $type): string
    {
        $descriptions = [
            'percentage_discount' => 'Save big with our percentage discount! Perfect opportunity to get your favorite items at reduced prices.',
            'fixed_discount' => 'Get a fixed amount off your purchase. The more you buy, the more you save!',
            'bogo' => 'Buy one, get one deal! Perfect for stocking up on your essentials.',
            'free_shipping' => 'Enjoy free shipping on your order. No minimum purchase required!',
            'bundle_deal' => 'Bundle multiple items together and save! Great value for money.',
            'flash_sale' => 'Limited time offer! Grab this deal before it expires.',
            'loyalty_points' => 'Earn extra loyalty points with this special promotion.',
            'referral' => 'Refer your friends and both of you get rewards!',
            'seasonal' => 'Celebrate the season with special discounts on selected items.',
            'clearance' => 'Final clearance sale! Get these items before they are gone forever.',
        ];

        return $descriptions[$type] ?? $this->faker->paragraph();
    }

    /**
     * Generate terms and conditions
     */
    private function generateTermsAndConditions(): string
    {
        $terms = [
            'This promotion cannot be combined with other offers.',
            'Valid for a limited time only.',
            'While supplies last.',
            'Discount applies to eligible items only.',
            'No cash value.',
            'Subject to availability.',
        ];

        return implode(' ', $this->faker->randomElements($terms, 3));
    }

    /**
     * Generate display rules
     */
    private function generateDisplayRules(string $type): array
    {
        return [
            'show_on_homepage' => $this->faker->boolean(60),
            'show_countdown' => in_array($type, ['flash_sale', 'seasonal']),
            'highlight_savings' => $this->faker->boolean(70),
            'banner_position' => $this->faker->randomElement(['top', 'middle', 'bottom']),
            'mobile_optimized' => true,
        ];
    }

    /**
     * Generate analytics data
     */
    private function generateAnalyticsData(): array
    {
        return [
            'impressions' => $this->faker->numberBetween(100, 10000),
            'clicks' => $this->faker->numberBetween(10, 1000),
            'conversions' => $this->faker->numberBetween(1, 100),
            'revenue_generated' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }

    /**
     * Create a percentage discount promotion
     */
    public function percentageDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage_discount',
            'name' => 'Percentage Discount - '.$this->faker->word(),
            'discount_value' => $this->faker->randomFloat(2, 10, 30),
            'code' => strtoupper($this->faker->bothify('SAVE##')),
        ]);
    }

    /**
     * Create a BOGO promotion
     */
    public function bogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bogo',
            'name' => 'Buy One Get One - '.$this->faker->word(),
            'buy_quantity' => 1,
            'get_quantity' => 1,
            'get_discount_percentage' => 100, // Free
            'discount_value' => null,
        ]);
    }

    /**
     * Create a flash sale promotion
     */
    public function flashSale(): static
    {
        $startsAt = now()->addHours(1);
        $endsAt = $startsAt->copy()->addHours(24);

        return $this->state(fn (array $attributes) => [
            'type' => 'flash_sale',
            'name' => 'Flash Sale - '.$this->faker->word(),
            'discount_value' => $this->faker->randomFloat(2, 20, 50),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'usage_limit' => $this->faker->numberBetween(50, 200),
            'is_active' => true,
        ]);
    }

    /**
     * Create an active promotion
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'starts_at' => now()->subDays(7),
            'ends_at' => now()->addMonths(1),
        ]);
    }

    /**
     * Create an inactive promotion
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an expired promotion
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDays(7),
        ]);
    }

    /**
     * Create a promotion with usage limit
     */
    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'usage_limit' => $limit,
            'usage_count' => $this->faker->numberBetween(0, min(50, $limit)),
        ]);
    }
}
