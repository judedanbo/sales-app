<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_categories');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('view_categories');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_categories');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category): bool
    {
        // Don't allow editing inactive categories unless user has special permission
        if (! $category->is_active && ! $user->hasPermissionTo('edit_inactive_categories')) {
            return false;
        }

        return $user->hasPermissionTo('edit_categories');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category): bool
    {
        // Prevent deletion if category has children
        if ($category->children()->exists()) {
            return false;
        }

        // Prevent deletion if category has active products (when Product model exists)
        if (class_exists('App\\Models\\Product') && $category->products()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('delete_categories');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('restore_categories');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        // Only allow force delete if no relationships exist
        if ($category->children()->withTrashed()->exists()) {
            return false;
        }

        // Check products if Product model exists
        if (class_exists('App\\Models\\Product') && $category->products()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('force_delete_categories');
    }

    /**
     * Determine whether the user can activate/deactivate categories.
     */
    public function toggleStatus(User $user, Category $category): bool
    {
        // Don't allow deactivating categories with active children
        if ($category->is_active && $category->activeChildren()->exists()) {
            return false;
        }

        return $user->hasPermissionTo('manage_category_status');
    }

    /**
     * Determine whether the user can move categories (change parent).
     */
    public function move(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage_category_hierarchy');
    }

    /**
     * Determine whether the user can reorder categories.
     */
    public function reorder(User $user): bool
    {
        return $user->hasPermissionTo('manage_category_hierarchy');
    }

    /**
     * Determine whether the user can view category statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermissionTo('view_category_reports') ||
               $user->hasPermissionTo('view_reports');
    }

    /**
     * Determine whether the user can bulk operations on categories.
     */
    public function bulkActions(User $user): bool
    {
        return $user->hasPermissionTo('bulk_edit_categories');
    }
}
