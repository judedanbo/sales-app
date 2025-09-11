<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeViewAny(Category::class);

        $query = Category::with(['parent', 'creator', 'updater'])
            ->withCount(['children', 'activeChildren', 'products']);

        // Apply filters
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id'); // Root categories
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('depth')) {
            $depth = (int) $request->depth;
            if ($depth === 0) {
                $query->whereNull('parent_id');
            } else {
                // This is a simplified depth filter - for exact depth matching
                // we'd need a more complex query with recursive CTEs
                $query->whereHas('parent', function ($q) use ($depth) {
                    if ($depth > 1) {
                        $q->whereNotNull('parent_id');
                    }
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'sort_order', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Add secondary sort for consistent ordering
        if ($sortBy !== 'sort_order') {
            $query->orderBy('sort_order', 'asc');
        }
        if ($sortBy !== 'name') {
            $query->orderBy('name', 'asc');
        }

        // Handle different view types
        $viewType = $request->get('view_type', 'list');
        if ($viewType === 'tree') {
            // For tree view, only get root categories with nested children
            $query->whereNull('parent_id')
                ->with(['descendants.creator', 'descendants.updater'])
                ->withCount(['descendants']);
        }

        // Paginate results
        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->paginate($perPage);

        return new CategoryCollection($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        $category = Category::create($validated);
        $category->load(['parent', 'creator', 'updater'])
            ->loadCount(['children', 'activeChildren', 'products']);

        return (new CategoryResource($category))
            ->additional(['message' => 'Category created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $this->authorizeView($category);

        $category->load(['parent', 'children.children', 'creator', 'updater'])
            ->loadCount(['children', 'activeChildren', 'products']);

        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $category->update($validated);
        $category->load(['parent', 'children', 'creator', 'updater'])
            ->loadCount(['children', 'activeChildren', 'products']);

        return (new CategoryResource($category))
            ->additional(['message' => 'Category updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->authorizeDelete($category);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted category.
     */
    public function restore(int $id): JsonResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $this->authorizeRestore($category);
        $category->restore();

        return response()->json([
            'success' => true,
            'message' => 'Category restored successfully',
            'data' => new CategoryResource($category->load(['parent', 'creator', 'updater'])),
        ]);
    }

    /**
     * Force delete a category.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $category = Category::withTrashed()->findOrFail($id);
        $this->authorizeForceDelete($category);
        $category->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Category permanently deleted',
        ]);
    }

    /**
     * Get categories with trashed records.
     */
    public function withTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Category::class);

        $query = Category::withTrashed()
            ->with(['parent', 'creator', 'updater'])
            ->withCount(['children', 'activeChildren', 'products']);

        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Get only trashed categories.
     */
    public function onlyTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Category::class);

        $query = Category::onlyTrashed()
            ->with(['parent', 'creator', 'updater'])
            ->withCount(['children', 'activeChildren', 'products']);

        $perPage = min($request->get('per_page', 15), 100);
        $categories = $query->orderBy('deleted_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Toggle category status (active/inactive).
     */
    public function toggleStatus(Category $category): JsonResponse
    {
        $this->authorize('toggleStatus', $category);

        $newStatus = ! $category->is_active;
        $category->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Category activated successfully' : 'Category deactivated successfully',
            'data' => new CategoryResource($category->load(['parent', 'creator', 'updater'])),
        ]);
    }

    /**
     * Move category to different parent.
     */
    public function move(Request $request, Category $category): JsonResponse
    {
        $this->authorize('move', $category);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Additional validation for circular references and depth
        if ($validated['parent_id']) {
            if ($category->wouldCreateCircularReference($validated['parent_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot move category under one of its own descendants.',
                ], 422);
            }
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category moved successfully',
            'data' => new CategoryResource($category->load(['parent', 'creator', 'updater'])),
        ]);
    }

    /**
     * Reorder categories within the same parent level.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('reorder', Category::class);

        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully',
        ]);
    }

    /**
     * Bulk update categories status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $this->authorize('bulkActions', Category::class);
        $this->validateBulkOperationLimits($request->input('category_ids', []));

        $validated = $request->validate([
            'category_ids' => 'required|array|max:100',
            'category_ids.*' => 'integer|exists:categories,id',
            'is_active' => 'required|boolean',
        ]);

        $updatedCount = Category::whereIn('id', $validated['category_ids'])
            ->update(['is_active' => $validated['is_active']]);

        $action = $validated['is_active'] ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Successfully {$action} {$updatedCount} categories",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Get category tree structure.
     */
    public function tree(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Category::class);

        $includeInactive = $request->boolean('include_inactive', false);

        $query = Category::whereNull('parent_id')
            ->with([
                'descendants' => function ($q) use ($includeInactive) {
                    if (! $includeInactive) {
                        $q->where('is_active', true);
                    }
                },
                'descendants.creator',
                'descendants.updater',
            ])
            ->withCount(['descendants', 'products']);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'include_inactive' => $includeInactive,
                'total_root_categories' => $categories->count(),
            ],
        ]);
    }

    /**
     * Get category statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewStatistics', Category::class);

        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'inactive_categories' => Category::where('is_active', false)->count(),
            'deleted_categories' => Category::onlyTrashed()->count(),
            'root_categories' => Category::whereNull('parent_id')->count(),
            'leaf_categories' => Category::whereDoesntHave('children')->count(),
            'categories_with_products' => Category::has('products')->count(),
            'by_depth' => [
                'level_0' => Category::whereNull('parent_id')->count(),
                'level_1' => Category::whereHas('parent', function ($q) {
                    $q->whereNull('parent_id');
                })->count(),
                'level_2_plus' => Category::whereHas('parent.parent')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
