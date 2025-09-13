<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkProductActionRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeViewAny(Product::class);

        $query = Product::with(['category', 'inventory', 'currentPrice', 'creator', 'updater'])
            ->withCount(['prices', 'classRequirements']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('price_from')) {
            $query->where('unit_price', '>=', $request->price_from);
        }

        if ($request->filled('price_to')) {
            $query->where('unit_price', '<=', $request->price_to);
        }

        if ($request->filled('unit_type')) {
            $query->where('unit_type', $request->unit_type);
        }

        if ($request->filled('brand')) {
            $query->where('brand', 'like', "%{$request->brand}%");
        }

        if ($request->filled('low_stock') && $request->low_stock === 'true') {
            $query->whereHas('inventory', function (Builder $q) {
                $q->where('is_low_stock', true);
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = [
            'name', 'sku', 'category_id', 'status', 'unit_price',
            'created_at', 'updated_at', 'brand', 'unit_type',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Paginate results
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return new ProductCollection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $this->authorizeCreate(Product::class);

        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $product = Product::create($validated);
        $product->load(['category', 'inventory', 'currentPrice', 'creator']);

        return (new ProductResource($product))
            ->additional(['message' => 'Product created successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $this->authorizeView($product);

        $product->load([
            'category',
            'inventory',
            'currentPrice',
            'prices' => function ($query) {
                $query->latest()->limit(5);
            },
            'creator',
            'updater',
        ]);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $product->update($validated);
        $product->load(['category', 'inventory', 'currentPrice', 'creator', 'updater']);

        return (new ProductResource($product))
            ->additional(['message' => 'Product updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorizeDelete($product);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Restore a soft deleted product.
     */
    public function restore(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $this->authorizeRestore($product);
        $product->restore();

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Force delete a product.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $this->authorizeForceDelete($product);
        $product->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Product permanently deleted',
        ]);
    }

    /**
     * Get products with trashed records.
     */
    public function withTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Product::class);

        $query = Product::withTrashed()
            ->with(['category', 'inventory', 'currentPrice', 'creator', 'updater'])
            ->withCount(['prices', 'classRequirements']);

        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Get only trashed products.
     */
    public function onlyTrashed(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Product::class);

        $query = Product::onlyTrashed()
            ->with(['category', 'inventory', 'currentPrice', 'creator', 'updater'])
            ->withCount(['prices', 'classRequirements']);

        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Bulk update products status.
     */
    public function bulkUpdateStatus(BulkProductActionRequest $request): JsonResponse
    {
        $this->authorizeBulkOperation('update', Product::class);
        $this->validateBulkOperationLimits($request->input('product_ids', []));

        $validated = $request->validated();

        $updatedCount = Product::whereIn('id', $validated['product_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "Updated {$updatedCount} products",
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(BulkProductActionRequest $request): JsonResponse
    {
        $this->authorizeBulkOperation('delete', Product::class);
        $this->validateBulkOperationLimits($request->input('product_ids', []));

        $validated = $request->validated();

        $deletedCount = Product::whereIn('id', $validated['product_ids'])
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} products",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Get product statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorizeStatistics(Product::class);

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'inactive' => Product::where('status', 'inactive')->count(),
            'discontinued' => Product::where('status', 'discontinued')->count(),
            'recent' => Product::whereBetween('created_at', [
                now()->subMonth(),
                now(),
            ])->count(),
            'low_stock' => Product::whereHas('inventory', function (Builder $q) {
                $q->where('is_low_stock', true);
            })->count(),
            'out_of_stock' => Product::whereHas('inventory', function (Builder $q) {
                $q->where('is_out_of_stock', true);
            })->count(),
            'by_category' => Product::with('category')
                ->selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_id' => $item->category_id,
                        'category_name' => $item->category->name ?? 'No Category',
                        'count' => $item->count,
                    ];
                }),
            'by_status' => Product::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_price_range' => [
                [
                    'range' => 'Under $10',
                    'count' => Product::where('unit_price', '<', 10)->count(),
                ],
                [
                    'range' => '$10 - $50',
                    'count' => Product::whereBetween('unit_price', [10, 50])->count(),
                ],
                [
                    'range' => '$50 - $100',
                    'count' => Product::whereBetween('unit_price', [50, 100])->count(),
                ],
                [
                    'range' => 'Over $100',
                    'count' => Product::where('unit_price', '>', 100)->count(),
                ],
            ],
            'value_breakdown' => [
                'total_value' => Product::sum('unit_price'),
                'average_price' => Product::avg('unit_price') ?? 0,
                'highest_price' => Product::max('unit_price') ?? 0,
                'lowest_price' => Product::min('unit_price') ?? 0,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Search products for autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Product::class);

        $query = $request->get('q', '');
        $limit = min($request->get('limit', 10), 50);

        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->orWhere('barcode', 'like', "%{$query}%")
            ->select('id', 'name', 'sku', 'unit_price', 'image_url')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
