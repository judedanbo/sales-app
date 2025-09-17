<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductPriceRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
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
     * Display the variants management page for the specified product.
     */
    public function variants(Product $product): Response
    {
        $this->authorizeView($product);

        // Load product with variants and their relationships
        $product->load([
            'category',
            'variants.inventory',
            'variants' => function ($query) {
                $query->orderBy('is_default', 'desc')
                    ->orderBy('name');
            },
        ]);

        // Convert variants to array to ensure proper serialization
        $variants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'size' => $variant->size,
                'color' => $variant->color,
                'material' => $variant->material,
                'status' => $variant->status,
                'is_default' => $variant->is_default,
                'inventory' => $variant->inventory ? [
                    'quantity_on_hand' => $variant->inventory->quantity_on_hand,
                    'quantity_available' => $variant->inventory->quantity_available,
                    'reorder_level' => $variant->inventory->reorder_level,
                    'is_out_of_stock' => $variant->inventory->quantity_on_hand <= 0,
                ] : null,
                'created_at' => $variant->created_at,
                'updated_at' => $variant->updated_at,
            ];
        });

        return Inertia::render('Products/Variants', [
            'product' => $product,
            'variants' => $variants,
        ]);
    }

    /**
     * Display the pricing management page for the specified product.
     */
    public function pricing(Product $product): Response
    {
        $this->authorizeView($product);

        // Load product with pricing relationships
        $product->load([
            'category',
            'inventory',
            'prices' => function ($query) {
                $query->with(['creator:id,name'])
                    ->orderBy('version_number', 'desc')
                    ->limit(50);
            },
        ]);

        // Get pricing rules that apply to this product
        // Note: This assumes PricingRule model exists, adjust based on your implementation
        $pricingRules = collect([]); // Placeholder - implement based on your PricingRule model

        // Format price history with complete data structure
        $priceHistory = $product->prices->map(function ($price) {
            return [
                'id' => $price->id,
                'price' => $price->price,
                'final_price' => $price->final_price ?? $price->price,
                'cost_price' => $price->cost_price,
                'markup_percentage' => $price->markup_percentage,
                'valid_from' => $price->valid_from,
                'valid_to' => $price->valid_to,
                'currency' => $price->currency ?? 'GHS',
                'bulk_discounts' => $price->bulk_discounts,
                'notes' => $price->notes,
                'status' => $price->status,
                'version_number' => $price->version_number,
                'created_by' => $price->created_by,
                'creator_name' => $price->creator->name ?? 'Unknown',
                'approved_by' => $price->approved_by,
                'approved_at' => $price->approved_at,
                'created_at' => $price->created_at,
                'updated_at' => $price->updated_at,
            ];
        });

        return Inertia::render('Products/Pricing', [
            'product' => $product,
            'pricing_rules' => $pricingRules,
            'price_history' => $priceHistory,
        ]);
    }

    /**
     * Display the inventory management page for the specified product.
     */
    public function inventory(Product $product): Response
    {
        $this->authorizeView($product);

        // Load product with inventory relationships
        $product->load([
            'category',
            'inventory',
        ]);

        // Get recent stock movements for this product
        $recentMovements = collect([]);
        try {
            $recentMovements = \App\Models\StockMovement::where('product_id', $product->id)
                ->whereNull('product_variant_id') // Only product-level movements
                ->with('user:id,name')
                ->orderBy('movement_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'movement_type' => $movement->movement_type, // Uses accessor
                        'quantity' => $movement->quantity, // Uses accessor
                        'reason' => $movement->reason, // Uses accessor
                        'notes' => $movement->notes,
                        'created_at' => $movement->created_at->toISOString(),
                        'movement_date' => $movement->movement_date->toISOString(),
                        'quantity_before' => $movement->quantity_before,
                        'quantity_after' => $movement->quantity_after,
                        'user' => $movement->user ? [
                            'id' => $movement->user->id,
                            'name' => $movement->user->name,
                        ] : null,
                    ];
                });
        } catch (\Exception $e) {
            // Log error but don't fail the page load
            \Log::error('Failed to load stock movements', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
            $recentMovements = collect([]);
        }

        return Inertia::render('Products/Inventory', [
            'product' => $product,
            'inventory' => $product->inventory,
            'recent_movements' => $recentMovements,
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
     * Store a new product variant.
     */
    public function storeVariant(Request $request, Product $product)
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku',
            'name' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'material' => 'nullable|string|max:100',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,discontinued',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
            'barcode' => 'nullable|string|max:100',
        ]);

        // Auto-generate SKU if not provided
        if (empty($validated['sku'])) {
            $baseSku = $product->sku;
            $suffix = '';
            if ($validated['size']) {
                $suffix .= '-'.$validated['size'];
            }
            if ($validated['color']) {
                $suffix .= '-'.substr($validated['color'], 0, 1);
            }
            if ($validated['material']) {
                $suffix .= '-'.substr($validated['material'], 0, 3);
            }
            $validated['sku'] = $baseSku.$suffix;
        }

        // Process dimensions
        if (isset($validated['dimensions'])) {
            $dimensions = array_filter($validated['dimensions'], fn ($value) => $value !== null && $value !== '');
            $validated['dimensions'] = empty($dimensions) ? null : $dimensions;
        }

        // If this is being set as default, remove default from other variants
        if ($validated['is_default'] ?? false) {
            $product->variants()->update(['is_default' => false]);
        }

        $variant = $product->variants()->create($validated);

        return redirect()->back()->with('message', 'Variant created successfully');
    }

    /**
     * Update a product variant.
     */
    public function updateVariant(Request $request, Product $product, $variantId)
    {
        $this->authorize('update', $product);

        $variant = $product->variants()->findOrFail($variantId);

        $validated = $request->validate([
            'sku' => 'nullable|string|max:100|unique:product_variants,sku,'.$variant->id,
            'name' => 'nullable|string|max:255',
            'size' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'material' => 'nullable|string|max:100',
            'unit_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'image_url' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive,discontinued',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
            'barcode' => 'nullable|string|max:100',
        ]);

        // Process dimensions
        if (isset($validated['dimensions'])) {
            $dimensions = array_filter($validated['dimensions'], fn ($value) => $value !== null && $value !== '');
            $validated['dimensions'] = empty($dimensions) ? null : $dimensions;
        }

        // If this is being set as default, remove default from other variants
        if ($validated['is_default'] ?? false) {
            $product->variants()->where('id', '!=', $variant->id)->update(['is_default' => false]);
        }

        $variant->update($validated);

        return redirect()->back()->with('message', 'Variant updated successfully');
    }

    /**
     * Delete a product variant.
     */
    public function destroyVariant(Product $product, $variantId)
    {
        $this->authorize('delete', $product);

        $variant = $product->variants()->findOrFail($variantId);

        // Prevent deletion of default variant
        if ($variant->is_default) {
            return redirect()->back()->with('error', 'Cannot delete the default variant');
        }

        $variant->delete();

        return redirect()->back()->with('message', 'Variant deleted successfully');
    }

    /**
     * Upload variant image.
     */
    public function uploadVariantImage(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $image = $request->file('image');
        $filename = time().'_'.Str::random(10).'.'.$image->getClientOriginalExtension();

        // Store in products subdirectory
        $path = $image->storeAs('products/variants', $filename, 'public');

        return response()->json([
            'success' => true,
            'image_url' => '/storage/'.$path,
            'message' => 'Image uploaded successfully',
        ]);
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

    /**
     * Create a new price version for a product.
     */
    public function storePrice(UpdateProductPriceRequest $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $validated = $request->validated();

        // Create new price record
        $productPrice = $product->prices()->create([
            'price' => $validated['price'],
            'final_price' => $validated['price'], // For now, same as price
            'cost_price' => $validated['cost_price'] ?? null,
            'markup_percentage' => $validated['markup_percentage'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'] ?? null,
            'currency' => $validated['currency'] ?? 'GHS',
            'bulk_discounts' => $validated['bulk_discounts'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending', // Always starts as pending
            'created_by' => auth()->id(),
        ]);

        // Auto-approve if user has manage_pricing permission
        if (auth()->user()->hasPermissionTo('manage_pricing')) {
            $productPrice->approve(auth()->id(), 'Auto-approved by pricing manager');

            // Update product unit_price
            $product->update(['unit_price' => $validated['price']]);

            $message = 'Price updated successfully';
        } else {
            $message = 'Price update request submitted for approval';
        }

        return redirect()->back()->with('message', $message);
    }

    /**
     * Approve a pending price.
     */
    public function approvePrice(Request $request, Product $product, ProductPrice $productPrice)
    {
        $this->authorizeUpdate($product);

        // Check permission
        if (! auth()->user()->hasPermissionTo('approve_pricing') && ! auth()->user()->hasPermissionTo('manage_pricing')) {
            abort(403, 'You do not have permission to approve prices.');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:500',
        ]);

        if ($productPrice->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending prices can be approved.');
        }

        // Approve the price
        $productPrice->approve(
            auth()->id(),
            $validated['approval_notes'] ?? 'Price approved'
        );

        // Update product unit_price
        $product->update(['unit_price' => $productPrice->price]);

        return redirect()->back()->with('message', 'Price approved and activated successfully');
    }

    /**
     * Reject a pending price.
     */
    public function rejectPrice(Request $request, Product $product, ProductPrice $productPrice)
    {
        $this->authorizeUpdate($product);

        // Check permission
        if (! auth()->user()->hasPermissionTo('approve_pricing') && ! auth()->user()->hasPermissionTo('manage_pricing')) {
            abort(403, 'You do not have permission to reject prices.');
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string|max:500',
        ]);

        if ($productPrice->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending prices can be rejected.');
        }

        // Reject the price
        $productPrice->reject(
            auth()->id(),
            $validated['approval_notes']
        );

        return redirect()->back()->with('message', 'Price request rejected');
    }

    /**
     * Get pending prices for approval.
     */
    public function getPendingPrices(Product $product)
    {
        $this->authorizeView($product);

        // Check permission
        if (! auth()->user()->hasPermissionTo('approve_pricing') && ! auth()->user()->hasPermissionTo('manage_pricing')) {
            abort(403, 'You do not have permission to view pending prices.');
        }

        $pendingPrices = $product->prices()
            ->pending()
            ->with(['creator'])
            ->latest()
            ->get()
            ->map(function ($price) {
                return [
                    'id' => $price->id,
                    'version_number' => $price->version_number,
                    'price' => $price->price,
                    'cost_price' => $price->cost_price,
                    'markup_percentage' => $price->markup_percentage,
                    'valid_from' => $price->valid_from,
                    'valid_to' => $price->valid_to,
                    'currency' => $price->currency,
                    'bulk_discounts' => $price->bulk_discounts,
                    'notes' => $price->notes,
                    'created_at' => $price->created_at,
                    'created_by' => $price->creator->name ?? 'Unknown',
                ];
            });

        return response()->json(['pending_prices' => $pendingPrices]);
    }

    /**
     * Update the reorder level for a product.
     */
    public function updateReorderLevel(Request $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $validated = $request->validate([
            'reorder_level' => 'required|numeric|min:0',
        ]);

        $oldLevel = $product->reorder_level;
        $newLevel = $validated['reorder_level'];

        $product->update([
            'reorder_level' => $newLevel,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => "Reorder level updated from {$oldLevel} to {$newLevel}",
            'product' => $product->fresh(['category', 'inventory']),
        ]);
    }

    /**
     * Adjust stock for a product.
     */
    public function adjustStock(Request $request, Product $product)
    {
        $this->authorizeUpdate($product);

        $validated = $request->validate([
            'adjustment_type' => 'required|in:increase,decrease,set',
            'quantity' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        $inventory = $product->inventory;
        if (! $inventory) {
            return response()->json([
                'error' => 'No inventory record exists for this product',
            ], 422);
        }

        $oldQuantity = $inventory->quantity_on_hand;
        $adjustmentQuantity = $validated['quantity'];
        $newQuantity = $oldQuantity;

        // Calculate new quantity based on adjustment type
        switch ($validated['adjustment_type']) {
            case 'increase':
                $newQuantity = $oldQuantity + $adjustmentQuantity;
                $quantityChange = $adjustmentQuantity;
                break;
            case 'decrease':
                $newQuantity = max(0, $oldQuantity - $adjustmentQuantity);
                $quantityChange = -($oldQuantity - $newQuantity); // Negative for decrease
                break;
            case 'set':
                $newQuantity = $adjustmentQuantity;
                $quantityChange = $newQuantity - $oldQuantity; // Can be positive or negative
                break;
        }

        // Update inventory
        $inventory->update([
            'quantity_on_hand' => $newQuantity,
            'quantity_available' => $inventory->quantity_available + ($newQuantity - $oldQuantity),
            'last_movement_at' => now(),
        ]);

        // Create stock movement record
        try {
            \App\Models\StockMovement::create([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'type' => 'adjustment', // Use the correct field name
                'quantity_change' => $quantityChange, // Use signed quantity change
                'quantity_before' => $oldQuantity,
                'quantity_after' => $newQuantity,
                'unit_cost' => null,
                'total_cost' => null,
                'currency' => 'GHS',
                'reference_type' => 'manual_adjustment',
                'reference_id' => null,
                'notes' => $validated['reason'].($validated['notes'] ? ' - '.$validated['notes'] : ''),
                'metadata' => json_encode([
                    'adjustment_type' => $validated['adjustment_type'],
                    'reason' => $validated['reason'],
                    'original_notes' => $validated['notes'],
                ]),
                'location' => null,
                'batch_number' => null,
                'expiry_date' => null,
                'user_id' => auth()->id(),
                'movement_date' => now(),
                'is_confirmed' => true,
                'confirmed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the stock adjustment
            \Log::error('Failed to create stock movement record', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'adjustment_type' => $validated['adjustment_type'],
                'quantity_change' => $quantityChange,
            ]);
        }

        return response()->json([
            'message' => 'Stock adjustment completed successfully',
            'adjustment' => [
                'type' => $validated['adjustment_type'],
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'change' => $newQuantity - $oldQuantity,
            ],
            'inventory' => $inventory->fresh(),
        ]);
    }
}
