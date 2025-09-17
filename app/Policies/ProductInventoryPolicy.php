<?php

namespace App\Policies;

use App\Models\ProductInventory;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductInventoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_inventory');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductInventory $productInventory): bool
    {
        return $user->can('view_inventory');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_inventory');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductInventory $productInventory): bool
    {
        return $user->can('edit_inventory');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductInventory $productInventory): bool
    {
        return $user->can('delete_inventory');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductInventory $productInventory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductInventory $productInventory): bool
    {
        return false;
    }
}
