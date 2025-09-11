<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'view_categories',
        'create_categories',
        'edit_categories',
        'delete_categories',
        'manage_category_status',
        'manage_category_hierarchy',
        'bulk_edit_categories',
    ]);

    $this->actingAs($this->user);
});

describe('Category Model', function () {
    it('can create a category with required fields', function () {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        expect($category->name)->toBe('Test Category');
        expect($category->slug)->toBe('test-category');
        expect($category->is_active)->toBeTrue();
    });

    it('auto-generates slug from name if not provided', function () {
        $category = Category::factory()->create([
            'name' => 'Test Category Name',
            'slug' => null,
        ]);

        expect($category->slug)->toBe('test-category-name');
    });

    it('can create hierarchical categories', function () {
        $parent = Category::factory()->create(['name' => 'Parent Category']);
        $child = Category::factory()->create([
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);

        expect($child->parent->id)->toBe($parent->id);
        expect($parent->children->count())->toBe(1);
        expect($parent->children->first()->id)->toBe($child->id);
    });

    it('calculates depth correctly', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        expect($parent->getDepth())->toBe(0);
        expect($child->getDepth())->toBe(1);
        expect($grandchild->getDepth())->toBe(2);
    });

    it('generates correct breadcrumb', function () {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $breadcrumb = $child->getBreadcrumb();

        expect($breadcrumb)->toHaveCount(2);
        expect($breadcrumb[0]['name'])->toBe('Parent');
        expect($breadcrumb[1]['name'])->toBe('Child');
    });

    it('detects circular references', function () {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['parent_id' => $child->id]);

        // This should NOT be a circular reference - grandchild moving to parent is fine
        expect($grandchild->wouldCreateCircularReference($parent->id))->toBeFalse();

        // This WOULD be a circular reference - parent moving under its own descendant
        expect($parent->wouldCreateCircularReference($grandchild->id))->toBeTrue();
        expect($parent->wouldCreateCircularReference($child->id))->toBeTrue();

        // Self-reference should always be circular
        expect($parent->wouldCreateCircularReference($parent->id))->toBeTrue();
    });
});

describe('Category Policy & Authorization', function () {
    it('user has correct category permissions', function () {
        expect($this->user->hasPermissionTo('view_categories'))->toBeTrue();
        expect($this->user->hasPermissionTo('create_categories'))->toBeTrue();
        expect($this->user->hasPermissionTo('edit_categories'))->toBeTrue();
        expect($this->user->hasPermissionTo('delete_categories'))->toBeTrue();
        expect($this->user->hasPermissionTo('manage_category_status'))->toBeTrue();
        expect($this->user->hasPermissionTo('manage_category_hierarchy'))->toBeTrue();
        expect($this->user->hasPermissionTo('bulk_edit_categories'))->toBeTrue();
    });

    it('can use policies to check authorization', function () {
        $category = Category::factory()->create();

        // Test policy methods
        expect($this->user->can('view', $category))->toBeTrue();
        expect($this->user->can('update', $category))->toBeTrue();
        expect($this->user->can('delete', $category))->toBeTrue();
        expect($this->user->can('toggleStatus', $category))->toBeTrue();
        expect($this->user->can('move', $category))->toBeTrue();
    });
});

describe('Category Database Operations', function () {
    it('can create categories with unique names at same level', function () {
        $parent = Category::factory()->create();

        // Should allow same names at different levels
        $category1 = Category::factory()->create([
            'name' => 'Same Name',
            'parent_id' => $parent->id,
        ]);

        $category2 = Category::factory()->create([
            'name' => 'Same Name',
            'parent_id' => null, // Different level (root)
        ]);

        expect($category1->name)->toBe('Same Name');
        expect($category2->name)->toBe('Same Name');
        expect($category1->parent_id)->toBe($parent->id);
        expect($category2->parent_id)->toBeNull();
    });

    it('tracks created_by and updated_by fields', function () {
        $category = Category::factory()->create([
            'name' => 'Test Category',
        ]);

        expect($category->created_by)->toBe($this->user->id);
        expect($category->updated_by)->toBe($this->user->id);
    });

    it('can soft delete categories', function () {
        $category = Category::factory()->create();

        $category->delete();

        expect($category->trashed())->toBeTrue();
        expect(Category::count())->toBe(0);
        expect(Category::withTrashed()->count())->toBe(1);
    });

    it('handles hierarchical queries correctly', function () {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child1 = Category::factory()->create(['name' => 'Child 1', 'parent_id' => $parent->id]);
        $child2 = Category::factory()->create(['name' => 'Child 2', 'parent_id' => $parent->id]);

        // Test scopes
        expect(Category::roots()->count())->toBe(1);
        expect(Category::leaves()->count())->toBe(2);
        expect($parent->children()->count())->toBe(2);
        expect($child1->isLeaf())->toBeTrue();
        expect($parent->isRoot())->toBeTrue();
    });
});
