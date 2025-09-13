<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_products');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // All users with view_products permission can see all products
        // Products are generally viewable across the system
        return $user->hasPermissionTo('view_products');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_products');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Check if product is discontinued and user lacks special permission
        if ($product->status === 'discontinued' && ! $user->hasPermissionTo('manage_product_status')) {
            return false;
        }

        return $user->hasPermissionTo('edit_products');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Prevent deletion if product is associated with active requirements
        if ($product->classProductRequirements()->where('is_active', true)->exists()) {
            return false;
        }

        return $user->hasPermissionTo('delete_products');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('restore_products');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Extra protection for force delete - must have special permission
        return $user->hasPermissionTo('force_delete_products');
    }

    /**
     * Determine whether the user can manage the product's status.
     */
    public function manageStatus(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('manage_product_status');
    }

    /**
     * Determine whether the user can manage the product's categories.
     */
    public function manageCategories(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('manage_product_categories');
    }

    /**
     * Determine whether the user can manage the product's pricing.
     */
    public function managePricing(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('manage_pricing');
    }

    /**
     * Determine whether the user can view the product's price history.
     */
    public function viewPriceHistory(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view_price_history');
    }

    /**
     * Determine whether the user can approve price changes for the product.
     */
    public function approvePriceChanges(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('approve_price_changes');
    }

    /**
     * Determine whether the user can bulk update products.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->hasPermissionTo('bulk_edit_products');
    }

    /**
     * Determine whether the user can export products.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_products');
    }

    /**
     * Determine whether the user can duplicate the product.
     */
    public function duplicate(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('duplicate_products');
    }

    /**
     * Determine whether the user can view product analytics.
     */
    public function viewAnalytics(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view_product_analytics');
    }

    /**
     * Determine whether the user can view inventory for the product.
     */
    public function viewInventory(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view_product_inventory');
    }

    /**
     * Determine whether the user can adjust stock for the product.
     */
    public function adjustStock(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('adjust_product_stock');
    }

    /**
     * Determine whether the user can view stock movements for the product.
     */
    public function viewStockMovements(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view_stock_movements');
    }

    /**
     * Determine whether the user can view school product usage.
     */
    public function viewSchoolUsage(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view_school_product_usage');
    }

    /**
     * Determine whether the user can assign products to classes.
     */
    public function assignToClasses(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('assign_products_to_classes');
    }

    /**
     * Determine whether the user can schedule price changes.
     */
    public function schedulePriceChanges(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('schedule_price_changes');
    }

    /**
     * Determine whether the user can perform bulk pricing operations.
     */
    public function bulkUpdatePricing(User $user): bool
    {
        return $user->hasPermissionTo('bulk_update_pricing');
    }
}
