<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useCurrency } from '@/composables/useCurrency';
import { type PricingRule, type Product } from '@/types';
import { Calculator, Percent, Tag } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    product: Product;
    pricingRules?: PricingRule[];
}

const props = defineProps<Props>();

const { formatCurrency } = useCurrency();

const quantity = ref(1);
const customerGroup = ref('regular');
const useDate = ref(new Date().toISOString().split('T')[0]);

const customerGroups = [
    { value: 'regular', label: 'Regular Customer' },
    { value: 'vip', label: 'VIP Customer' },
    { value: 'wholesale', label: 'Wholesale Customer' },
    { value: 'student', label: 'Student' },
    { value: 'teacher', label: 'Teacher' },
];

interface PriceCalculation {
    basePrice: number;
    subtotal: number;
    appliedRules: Array<{
        rule: PricingRule;
        discount: number;
        description: string;
    }>;
    totalDiscount: number;
    finalPrice: number;
    taxAmount: number;
    totalWithTax: number;
    savings: number;
    pricePerUnit: number;
}

const calculation = computed((): PriceCalculation => {
    const basePrice = props.product.unit_price || 0;
    const subtotal = basePrice * quantity.value;
    const appliedRules: Array<{ rule: PricingRule; discount: number; description: string }> = [];
    let totalDiscount = 0;

    // Apply pricing rules
    if (props.pricingRules) {
        const activeRules = props.pricingRules
            .filter((rule) => rule.status === 'active')
            .filter((rule) => {
                // Check if rule is valid for the selected date
                const ruleStart = new Date(rule.valid_from);
                const ruleEnd = rule.valid_to ? new Date(rule.valid_to) : null;
                const selectedDate = new Date(useDate.value);

                return selectedDate >= ruleStart && (!ruleEnd || selectedDate <= ruleEnd);
            })
            .sort((a, b) => a.priority - b.priority); // Lower priority number = higher priority

        for (const rule of activeRules) {
            let ruleApplies = false;
            let discount = 0;
            let description = '';

            // Check if rule applies to this product
            if (
                rule.applies_to === 'all_products' ||
                (rule.applies_to === 'specific_products' && rule.product_ids?.includes(props.product.id)) ||
                (rule.applies_to === 'categories' && rule.category_ids?.includes(props.product.category_id))
            ) {
                // Check conditions
                for (const condition of rule.conditions) {
                    switch (condition.field) {
                        case 'quantity':
                            if (condition.operator === 'greater_than' && quantity.value > condition.value) {
                                ruleApplies = true;
                            } else if (condition.operator === 'greater_than_or_equal' && quantity.value >= condition.value) {
                                ruleApplies = true;
                            }
                            break;
                        case 'customer_group':
                            if (condition.operator === 'equals' && customerGroup.value === condition.value) {
                                ruleApplies = true;
                            }
                            break;
                        case 'subtotal':
                            if (condition.operator === 'greater_than' && subtotal > condition.value) {
                                ruleApplies = true;
                            }
                            break;
                    }
                }

                // If no conditions specified, rule applies
                if (rule.conditions.length === 0) {
                    ruleApplies = true;
                }
            }

            if (ruleApplies && rule.actions.length > 0) {
                const action = rule.actions[0];

                switch (action.type) {
                    case 'percentage_discount':
                        discount = (subtotal - totalDiscount) * (action.value / 100);
                        if (action.max_discount && discount > action.max_discount) {
                            discount = action.max_discount;
                        }
                        description = `${action.value}% discount`;
                        break;
                    case 'fixed_discount':
                        discount = Math.min(action.value, subtotal - totalDiscount);
                        description = `${formatCurrency(action.value)} discount`;
                        break;
                    case 'fixed_price':
                        const currentPrice = subtotal - totalDiscount;
                        discount = Math.max(0, currentPrice - action.value * quantity.value);
                        description = `Fixed price: ${formatCurrency(action.value)} per unit`;
                        break;
                    case 'buy_x_get_y':
                        if (quantity.value >= (rule.conditions[0]?.value || 1)) {
                            const freeItems = Math.floor(quantity.value / (rule.conditions[0]?.value || 1)) * (action.free_quantity || 1);
                            discount = freeItems * basePrice;
                            description = `Buy ${rule.conditions[0]?.value || 1} get ${action.free_quantity || 1} free`;
                        }
                        break;
                }

                if (discount > 0) {
                    appliedRules.push({ rule, discount, description });
                    totalDiscount += discount;

                    // Check if other rules can be combined
                    if (!rule.can_be_combined) {
                        break;
                    }
                }
            }
        }
    }

    const finalPrice = Math.max(0, subtotal - totalDiscount);
    const taxRate = props.product.tax_rate || 0;
    const taxAmount = finalPrice * taxRate;
    const totalWithTax = finalPrice + taxAmount;
    const savings = totalDiscount;
    const pricePerUnit = finalPrice / quantity.value;

    return {
        basePrice,
        subtotal,
        appliedRules,
        totalDiscount,
        finalPrice,
        taxAmount,
        totalWithTax,
        savings,
        pricePerUnit,
    };
});

const formatPrice = (amount: number) => {
    if (amount === null || amount === undefined) return 'GH₵0.00';
    return formatCurrency(Number(amount));
};

const savingsPercentage = computed(() => {
    if (calculation.value.subtotal === 0) return 0;
    return (calculation.value.savings / calculation.value.subtotal) * 100;
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Calculator class="h-5 w-5" />
                Pricing Calculator
            </CardTitle>
            <CardDescription> Calculate final prices with quantity discounts and promotional rules. </CardDescription>
        </CardHeader>
        <CardContent class="space-y-6">
            <!-- Input Controls -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="quantity">Quantity</Label>
                    <Input id="quantity" v-model.number="quantity" type="number" min="1" step="1" />
                </div>
                <div class="space-y-2">
                    <Label for="customerGroup">Customer Group</Label>
                    <select
                        id="customerGroup"
                        v-model="customerGroup"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option v-for="group in customerGroups" :key="group.value" :value="group.value">
                            {{ group.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="useDate">Pricing Date</Label>
                    <Input id="useDate" v-model="useDate" type="date" />
                </div>
            </div>

            <!-- Price Breakdown -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium">Price Breakdown</h3>

                <div class="space-y-3">
                    <!-- Base Price -->
                    <div class="flex items-center justify-between">
                        <span>Base Price ({{ quantity }} × {{ formatPrice(calculation.basePrice) }})</span>
                        <span class="font-medium">{{ formatPrice(calculation.subtotal) }}</span>
                    </div>

                    <!-- Applied Rules -->
                    <div v-if="calculation.appliedRules.length > 0" class="space-y-2">
                        <div class="flex items-center gap-2 text-sm font-medium text-green-600 dark:text-green-400">
                            <Tag class="h-4 w-4" />
                            Applied Discounts
                        </div>
                        <div
                            v-for="appliedRule in calculation.appliedRules"
                            :key="appliedRule.rule.id"
                            class="flex items-center justify-between pl-6 text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary" class="text-xs">
                                    {{ appliedRule.rule.name }}
                                </Badge>
                                <span class="text-gray-600 dark:text-gray-400">{{ appliedRule.description }}</span>
                            </div>
                            <span class="font-medium text-green-600 dark:text-green-400"> -{{ formatPrice(appliedRule.discount) }} </span>
                        </div>
                    </div>

                    <Separator />

                    <!-- Subtotal after discounts -->
                    <div class="flex items-center justify-between font-medium">
                        <span>Subtotal after discounts</span>
                        <span>{{ formatPrice(calculation.finalPrice) }}</span>
                    </div>

                    <!-- Tax -->
                    <div v-if="calculation.taxAmount > 0" class="flex items-center justify-between text-sm">
                        <span>Tax ({{ ((product.tax_rate || 0) * 100).toFixed(2) }}%)</span>
                        <span>{{ formatPrice(calculation.taxAmount) }}</span>
                    </div>

                    <Separator />

                    <!-- Final Total -->
                    <div class="flex items-center justify-between text-lg font-bold">
                        <span>Total</span>
                        <span>{{ formatPrice(calculation.totalWithTax) }}</span>
                    </div>

                    <!-- Price per unit -->
                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Price per unit</span>
                        <span>{{ formatPrice(calculation.pricePerUnit) }}</span>
                    </div>
                </div>
            </div>

            <!-- Savings Summary -->
            <div v-if="calculation.savings > 0" class="rounded-lg bg-green-50 p-4 dark:bg-green-900/10">
                <div class="mb-2 flex items-center gap-2 text-green-700 dark:text-green-400">
                    <Percent class="h-5 w-5" />
                    <span class="font-medium">You Save!</span>
                </div>
                <div class="space-y-1">
                    <div class="flex justify-between">
                        <span>Total Savings:</span>
                        <span class="font-bold">{{ formatPrice(calculation.savings) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span>Savings Percentage:</span>
                        <span>{{ savingsPercentage.toFixed(1) }}%</span>
                    </div>
                </div>
            </div>

            <!-- No Rules Applied -->
            <div v-else-if="pricingRules && pricingRules.length > 0" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/10">
                <div class="text-center text-gray-600 dark:text-gray-400">
                    <Tag class="mx-auto mb-2 h-8 w-8" />
                    <p class="font-medium">No pricing rules applied</p>
                    <p class="text-sm">Try adjusting quantity or customer group to see available discounts.</p>
                </div>
            </div>

            <!-- Quick Quantity Buttons -->
            <div class="space-y-2">
                <Label>Quick Quantities</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="qty in [1, 5, 10, 25, 50, 100]"
                        :key="qty"
                        size="sm"
                        variant="outline"
                        :class="{ 'bg-primary text-primary-foreground dark:text-gray-600': quantity === qty }"
                        @click="quantity = qty"
                    >
                        {{ qty }}
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
