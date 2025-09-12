<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): Response
    {
        $this->authorizeViewAny(Category::class);

        $query = Category::with(['parent', 'creator', 'updater'])
            ->withCount(['children', 'activeChildren', 'products']);

        // Get filter data from request
        $filters = $request->all();

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null' || $filters['parent_id'] === '') {
                $query->whereNull('parent_id'); // Root categories
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Date range filters
        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['updated_from'])) {
            $query->whereDate('updated_at', '>=', $filters['updated_from']);
        }

        if (! empty($filters['updated_to'])) {
            $query->whereDate('updated_at', '<=', $filters['updated_to']);
        }

        // Creator filter
        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Children count filter
        if (isset($filters['has_children'])) {
            if ($filters['has_children'] === '1') {
                $query->has('children');
            } elseif ($filters['has_children'] === '0') {
                $query->doesntHave('children');
            }
        }

        // Products count filter
        if (isset($filters['has_products'])) {
            if ($filters['has_products'] === '1') {
                $query->has('products');
            } elseif ($filters['has_products'] === '0') {
                $query->doesntHave('products');
            }
        }

        // Sort order range
        if (! empty($filters['sort_order_from'])) {
            $query->where('sort_order', '>=', $filters['sort_order_from']);
        }

        if (! empty($filters['sort_order_to'])) {
            $query->where('sort_order', '<=', $filters['sort_order_to']);
        }

        // Include deleted categories if requested
        if (! empty($filters['include_deleted']) && $filters['include_deleted'] === '1') {
            $query->withTrashed();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'sort_order';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

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

        // Handle pagination
        $page = $filters['page'] ?? 1;
        $categories = $query->paginate(15, ['*'], 'page', $page);

        // Get parent categories for filter dropdown
        $parentCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        // Get users who have created categories for creator filter
        $creators = User::whereIn('id', function ($query) {
            $query->select('created_by')
                ->from('categories')
                ->whereNotNull('created_by')
                ->distinct();
        })->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'parent_id' => $filters['parent_id'] ?? '',
                'is_active' => $filters['is_active'] ?? '',
                'created_from' => $filters['created_from'] ?? '',
                'created_to' => $filters['created_to'] ?? '',
                'updated_from' => $filters['updated_from'] ?? '',
                'updated_to' => $filters['updated_to'] ?? '',
                'created_by' => $filters['created_by'] ?? '',
                'has_children' => $filters['has_children'] ?? '',
                'has_products' => $filters['has_products'] ?? '',
                'sort_order_from' => $filters['sort_order_from'] ?? '',
                'sort_order_to' => $filters['sort_order_to'] ?? '',
                'include_deleted' => $filters['include_deleted'] ?? '',
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'parentCategories' => $parentCategories,
            'creators' => $creators,
        ]);
    }

    /**
     * Display tree view of categories.
     */
    public function tree(Request $request): Response
    {
        $this->authorizeViewAny(Category::class);

        $includeInactive = $request->boolean('include_inactive', false);

        $query = Category::whereNull('parent_id')
            ->with([
                'children' => function ($q) use ($includeInactive) {
                    if (! $includeInactive) {
                        $q->where('is_active', true);
                    }
                    $q->with([
                        'children' => function ($q2) use ($includeInactive) {
                            if (! $includeInactive) {
                                $q2->where('is_active', true);
                            }
                            $q2->with([
                                'children' => function ($q3) use ($includeInactive) {
                                    if (! $includeInactive) {
                                        $q3->where('is_active', true);
                                    }
                                    $q3->with(['creator', 'updater'])
                                        ->withCount(['children', 'products']);
                                },
                                'creator',
                                'updater',
                            ])
                                ->withCount(['children', 'products']);
                        },
                        'creator',
                        'updater',
                    ])
                        ->withCount(['children', 'products']);
                },
                'creator',
                'updater',
            ])
            ->withCount(['children', 'products']);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('Categories/Tree', [
            'categories' => $categories,
            'includeInactive' => $includeInactive,
        ]);
    }

    /**
     * Get form data for category creation/editing (AJAX endpoint).
     */
    public function getFormData(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Category::class);

        $excludeId = $request->get('exclude_id'); // For edit mode, exclude self from parent options

        $parentCategories = Category::where('is_active', true)
            ->when($excludeId, function ($query, $excludeId) {
                // Exclude the category being edited and its descendants
                $category = Category::find($excludeId);
                if ($category) {
                    $excludeIds = $category->getAllDescendantIds();
                    $query->whereNotIn('id', $excludeIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id'])
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->getFullNameAttribute(),
                ];
            });

        return response()->json([
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        $category = Category::create($validated);

        if ($request->header('X-Inertia')) {
            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        }

        return redirect()->route('categories.show', $category)->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): Response
    {
        $this->authorizeView($category);

        $category->load([
            'parent',
            'children' => function ($query) {
                $query->with(['creator', 'updater'])
                    ->withCount(['children', 'activeChildren', 'products'])
                    ->orderBy('sort_order')
                    ->orderBy('name');
            },
            'creator',
            'updater',
        ])->loadCount(['children', 'activeChildren', 'products']);

        // Get breadcrumb for navigation
        $breadcrumb = $category->getBreadcrumb();

        return Inertia::render('Categories/Show', [
            'category' => $category,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();
        $category->update($validated);

        if ($request->header('X-Inertia')) {
            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        }

        return redirect()->route('categories.show', $category)->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category)
    {
        $this->authorizeDelete($category);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }

    /**
     * Toggle category status.
     */
    public function toggleStatus(Category $category)
    {
        $this->authorize('toggleStatus', $category);

        $newStatus = ! $category->is_active;
        $category->update(['is_active' => $newStatus]);

        $message = $newStatus ? 'Category activated successfully.' : 'Category deactivated successfully.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Move category to different parent.
     */
    public function move(Request $request, Category $category)
    {
        $this->authorize('move', $category);

        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        // Additional validation for circular references
        if ($validated['parent_id']) {
            if ($category->wouldCreateCircularReference($validated['parent_id'])) {
                return redirect()->back()->with('error', 'Cannot move category under one of its own descendants.');
            }
        }

        $category->update($validated);

        return redirect()->back()->with('success', 'Category moved successfully.');
    }

    /**
     * Display dashboard with category statistics.
     */
    public function dashboard(): Response
    {
        $this->authorize('viewStatistics', Category::class);

        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::where('is_active', true)->count(),
            'inactive_categories' => Category::where('is_active', false)->count(),
            'root_categories' => Category::whereNull('parent_id')->count(),
            'leaf_categories' => Category::whereDoesntHave('children')->count(),
            'categories_with_products' => Category::has('products')->count(),
            'recent_categories' => Category::with(['creator'])
                ->latest()
                ->take(5)
                ->get(),
            'by_depth' => [
                'level_0' => Category::whereNull('parent_id')->count(),
                'level_1' => Category::whereHas('parent', function ($q) {
                    $q->whereNull('parent_id');
                })->count(),
                'level_2_plus' => Category::whereHas('parent.parent')->count(),
            ],
            'most_used_categories' => Category::withCount('products')
                ->having('products_count', '>', 0)
                ->orderByDesc('products_count')
                ->take(10)
                ->get(['id', 'name', 'products_count']),
        ];

        return Inertia::render('Categories/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
