<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $this->authorizeViewAny(Product::class);

        $query = Product::with(['category', 'inventory', 'currentPrice'])
            ->withCount(['prices', 'classRequirements']);

        // Get filter data from request
        $filters = $request->all();

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['price_from'])) {
            $query->where('unit_price', '>=', $filters['price_from']);
        }

        if (! empty($filters['price_to'])) {
            $query->where('unit_price', '<=', $filters['price_to']);
        }

        if (! empty($filters['unit_type'])) {
            $query->where('unit_type', $filters['unit_type']);
        }

        if (! empty($filters['brand'])) {
            $query->where('brand', 'like', "%{$filters['brand']}%");
        }

        if (! empty($filters['low_stock']) && $filters['low_stock'] === 'true') {
            $query->whereHas('inventory', function (Builder $q) {
                $q->whereRaw('quantity_on_hand <= minimum_stock_level')
                    ->whereNotNull('minimum_stock_level');
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        $allowedSorts = [
            'name', 'sku', 'category_id', 'status', 'unit_price',
            'created_at', 'updated_at', 'brand', 'unit_type',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Handle pagination
        $page = $filters['page'] ?? 1;
        $products = $query->paginate(15, ['*'], 'page', $page);

        // Get statistics for the dashboard
        $statistics = $this->getProductStatistics();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
                'status' => $filters['status'] ?? '',
                'price_from' => $filters['price_from'] ?? '',
                'price_to' => $filters['price_to'] ?? '',
                'low_stock' => $filters['low_stock'] ?? '',
                'unit_type' => $filters['unit_type'] ?? '',
                'brand' => $filters['brand'] ?? '',
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'statistics' => $statistics,
            'categories' => $this->getCategoriesForSelect(),
            'unitTypes' => $this->getUnitTypesForSelect(),
            'brands' => $this->getBrandsForSelect(),
        ]);
    }

    /**
     * Get form data for product creation/editing (AJAX endpoint).
     */
    public function getFormData(): \Illuminate\Http\JsonResponse
    {
        $this->authorizeViewAny(Product::class);

        return response()->json([
            'categories' => $this->getCategoriesForSelect(),
            'unitTypes' => $this->getUnitTypesForSelect(),
            'brands' => $this->getBrandsForSelect(),
            'skuPatterns' => $this->getSkuPatternsForSelect(),
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'discontinued', 'label' => 'Discontinued'],
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request)
    {
        $this->authorizeCreate(Product::class);

        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $product = Product::create($validated);

        return redirect()->back()->with([
            'success' => 'Product created successfully.',
            'product' => $product,
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): Response
    {
        $this->authorizeView($product);

        $product->load([
            'category',
            'inventory',
            'currentPrice',
            'prices' => function ($query) {
                $query->latest()->limit(10);
            },
            'creator',
            'updater',
        ]);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        $this->authorizeUpdate($product);

        $product->load(['category', 'inventory', 'currentPrice']);

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => $this->getCategoriesForSelect(),
            'unitTypes' => $this->getUnitTypesForSelect(),
            'brands' => $this->getBrandsForSelect(),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $product->update($validated);

        // Reload the product with relationships for returning
        $product->load([
            'category',
            'inventory',
            'currentPrice',
        ]);

        // If this is an Inertia request from modal, return the updated product
        if ($request->header('X-Inertia')) {
            return redirect()->back()->with([
                'success' => 'Product updated successfully.',
                'product' => $product,
            ]);
        }

        // For non-Inertia requests, redirect to show page
        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $this->authorizeDelete($product);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Upload a new product image.
     */
    public function uploadImage(UploadProductImageRequest $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $file = $request->file('image');

        // Delete old image if it exists
        if ($product->image_url) {
            $this->deleteImageFile($product->image_url);
        }

        // Generate unique filename
        $filename = $this->generateImageFilename($file, $product);

        // Store the image
        $path = $file->storeAs('products', $filename, 'public');

        // Update product with new image URL
        $imageUrl = Storage::url($path);
        $product->update([
            'image_url' => $imageUrl,
            'updated_by' => auth()->id(),
        ]);

        return back()->with([
            'success' => 'Product image uploaded successfully.',
            'imageUrl' => $imageUrl,
            'product' => $product->fresh(['category', 'inventory', 'currentPrice', 'creator', 'updater']),
        ]);
    }

    /**
     * Delete the product image.
     */
    public function deleteImage(Product $product)
    {
        $this->authorizeUpdate($product);

        if (! $product->image_url) {
            return back()->withErrors(['image' => 'No image to delete.']);
        }

        // Delete the image file
        $this->deleteImageFile($product->image_url);

        // Update product to remove image URL
        $product->update([
            'image_url' => null,
            'updated_by' => auth()->id(),
        ]);

        return back()->with([
            'success' => 'Product image deleted successfully.',
            'product' => $product->fresh(['category', 'inventory', 'currentPrice', 'creator', 'updater']),
        ]);
    }

    /**
     * Get product statistics.
     */
    private function getProductStatistics(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('status', 'active')->count(),
            'inactive' => Product::where('status', 'inactive')->count(),
            'discontinued' => Product::where('status', 'discontinued')->count(),
            'recent' => Product::whereBetween('created_at', [
                now()->subMonth(),
                now(),
            ])->count(),
            'low_stock' => Product::whereHas('inventory', function (Builder $q) {
                $q->whereRaw('quantity_on_hand <= minimum_stock_level')
                    ->whereNotNull('minimum_stock_level');
            })->count(),
            'out_of_stock' => Product::whereHas('inventory', function (Builder $q) {
                $q->where('quantity_on_hand', '<=', 0);
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
                ->pluck('count', 'status')
                ->toArray(),
            'by_price_range' => [
                [
                    'range' => 'Under GH₵10',
                    'count' => Product::where('unit_price', '<', 10)->count(),
                ],
                [
                    'range' => 'GH₵10 - GH₵50',
                    'count' => Product::whereBetween('unit_price', [10, 50])->count(),
                ],
                [
                    'range' => 'GH₵50 - GH₵100',
                    'count' => Product::whereBetween('unit_price', [50, 100])->count(),
                ],
                [
                    'range' => 'Over GH₵100',
                    'count' => Product::where('unit_price', '>', 100)->count(),
                ],
            ],
            'value_breakdown' => [
                'total_value' => Product::sum('unit_price') ?? 0,
                'average_price' => Product::avg('unit_price') ?? 0,
                'highest_price' => Product::max('unit_price') ?? 0,
                'lowest_price' => Product::min('unit_price') ?? 0,
            ],
        ];
    }

    /**
     * Get categories formatted for select components.
     */
    private function getCategoriesForSelect(): array
    {
        return Category::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->name,
                ];
            })
            ->toArray();
    }

    /**
     * Get unique unit types from existing products.
     */
    private function getUnitTypesForSelect(): array
    {
        $unitTypes = Product::select('unit_type')
            ->whereNotNull('unit_type')
            ->distinct()
            ->orderBy('unit_type')
            ->pluck('unit_type')
            ->toArray();

        // Add common unit types if they don't exist
        $commonUnitTypes = ['piece', 'kg', 'g', 'liter', 'ml', 'meter', 'cm', 'box', 'pack', 'set'];
        $unitTypes = array_unique(array_merge($unitTypes, $commonUnitTypes));
        sort($unitTypes);

        return array_map(function ($unitType) {
            return [
                'value' => $unitType,
                'label' => ucfirst($unitType),
            ];
        }, $unitTypes);
    }

    /**
     * Get unique brands from existing products.
     */
    private function getBrandsForSelect(): array
    {
        return Product::select('brand')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->map(function ($brand) {
                return [
                    'value' => $brand,
                    'label' => $brand,
                ];
            })
            ->toArray();
    }

    /**
     * Get SKU pattern options for product creation.
     */
    private function getSkuPatternsForSelect(): array
    {
        return [
            ['value' => 'auto', 'label' => 'Auto-generate from name'],
            ['value' => 'PRD-', 'label' => 'Product Pattern (PRD-XXXX)'],
            ['value' => 'ITM-', 'label' => 'Item Pattern (ITM-XXXX)'],
            ['value' => 'GDS-', 'label' => 'Goods Pattern (GDS-XXXX)'],
            ['value' => 'STK-', 'label' => 'Stock Pattern (STK-XXXX)'],
            ['value' => 'custom', 'label' => 'Enter custom SKU'],
        ];
    }

    /**
     * Generate a unique filename for the uploaded image.
     */
    private function generateImageFilename($file, Product $product): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);
        $productSlug = Str::slug($product->name, '-');

        return "{$productSlug}-{$product->id}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Delete an image file from storage.
     */
    private function deleteImageFile(?string $imageUrl): void
    {
        if (! $imageUrl) {
            return;
        }

        // Extract the file path from the URL
        // Assuming URLs are in format /storage/products/filename.ext
        $path = str_replace('/storage/', '', $imageUrl);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
