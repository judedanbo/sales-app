<?php

namespace App\Policies;

use App\Models\ProductPrice;
use App\Models\User;

class ProductPricePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_price_history');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductPrice $productPrice): bool
    {
        return $user->hasPermissionTo('view_price_history');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_pricing');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductPrice $productPrice): bool
    {
        // Cannot update active or rejected prices
        if ($productPrice->status === 'active' || $productPrice->status === 'rejected') {
            return false;
        }

        // Users can only edit their own price submissions unless they have approval permission
        if ($productPrice->created_by !== $user->id && ! $user->hasPermissionTo('approve_price_changes')) {
            return false;
        }

        return $user->hasPermissionTo('manage_pricing');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductPrice $productPrice): bool
    {
        // Cannot delete active prices
        if ($productPrice->status === 'active') {
            return false;
        }

        // Users can only delete their own price submissions unless they have approval permission
        if ($productPrice->created_by !== $user->id && ! $user->hasPermissionTo('approve_price_changes')) {
            return false;
        }

        return $user->hasPermissionTo('manage_pricing');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductPrice $productPrice): bool
    {
        return $user->hasPermissionTo('manage_pricing') && $user->hasPermissionTo('approve_price_changes');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductPrice $productPrice): bool
    {
        return $user->hasPermissionTo('manage_pricing') && $user->hasPermissionTo('approve_price_changes');
    }

    /**
     * Determine whether the user can approve or reject the price.
     */
    public function approve(User $user, ProductPrice $productPrice): bool
    {
        // Cannot approve own submissions (separation of duties)
        if ($productPrice->created_by === $user->id) {
            return false;
        }

        // Can only approve pending prices
        if ($productPrice->status !== 'pending') {
            return false;
        }

        return $user->hasPermissionTo('approve_price_changes');
    }

    /**
     * Determine whether the user can schedule price changes.
     */
    public function schedule(User $user, ProductPrice $productPrice): bool
    {
        return $user->hasPermissionTo('schedule_price_changes');
    }

    /**
     * Determine whether the user can view pricing analytics.
     */
    public function viewAnalytics(User $user, ProductPrice $productPrice): bool
    {
        return $user->hasPermissionTo('view_product_analytics');
    }

    /**
     * Determine whether the user can perform bulk pricing operations.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->hasPermissionTo('bulk_update_pricing');
    }

    /**
     * Determine whether the user can export price data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_products');
    }
}
