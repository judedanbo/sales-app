<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    /**
     * Display the inventory dashboard.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProductInventory::class);

        // Get filter parameters
        $filters = $request->only(['search', 'product_id', 'category_id', 'status']);

        // Get inventory statistics
        $statistics = $this->getInventoryStatistics();

        // Get recent stock movements (last 10)
        $recentMovements = $this->getRecentMovements($filters);

        // Get low stock products
        $lowStockProducts = $this->getLowStockProducts();

        return Inertia::render('Inventory/Index', [
            'statistics' => $statistics,
            'movements' => $recentMovements,
            'lowStockProducts' => $lowStockProducts,
            'filters' => $filters,
        ]);
    }

    /**
     * Display stock movements with filtering and pagination.
     */
    public function movements(Request $request): Response
    {
        $this->authorize('viewAny', StockMovement::class);

        $filters = $request->only([
            'search', 'product_id', 'variant_id', 'movement_type',
            'date_from', 'date_to', 'created_by', 'reference_type',
            'sort_by', 'sort_direction',
        ]);

        $query = StockMovement::with(['product:id,name,sku', 'user:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('product', function (Builder $productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                })->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (! empty($filters['variant_id'])) {
            $query->where('product_variant_id', $filters['variant_id']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('type', $filters['movement_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('movement_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('movement_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('user_id', $filters['created_by']);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'movement_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        $allowedSorts = [
            'movement_date', 'created_at', 'type', 'quantity_change',
            'quantity_before', 'quantity_after',
        ];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        // Paginate results
        $movements = $query->paginate(50);

        // Transform the data for frontend
        $movements->getCollection()->transform(function ($movement) {
            return [
                'id' => $movement->id,
                'product_id' => $movement->product_id,
                'product_variant_id' => $movement->product_variant_id,
                'movement_type' => $movement->movement_type, // Uses accessor
                'quantity' => $movement->quantity, // Uses accessor
                'reason' => $movement->reason, // Uses accessor
                'notes' => $movement->notes,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'unit_cost' => $movement->unit_cost,
                'total_cost' => $movement->total_cost,
                'currency' => $movement->currency,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'location' => $movement->location,
                'batch_number' => $movement->batch_number,
                'movement_date' => $movement->movement_date->toISOString(),
                'created_at' => $movement->created_at->toISOString(),
                'is_confirmed' => $movement->is_confirmed,
                'product' => $movement->product ? [
                    'id' => $movement->product->id,
                    'name' => $movement->product->name,
                    'sku' => $movement->product->sku,
                ] : null,
                'user' => $movement->user ? [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                ] : null,
            ];
        });

        return Inertia::render('Inventory/Movements', [
            'movements' => $movements,
            'filters' => $filters,
        ]);
    }

    /**
     * Get inventory statistics.
     */
    private function getInventoryStatistics(): array
    {
        $totalProducts = Product::has('inventory')->count();
        $totalVariants = Product::has('variants.inventory')->count();

        // Calculate total stock value
        $totalStockValue = ProductInventory::whereNull('product_variant_id')
            ->join('products', 'product_inventories.product_id', '=', 'products.id')
            ->selectRaw('SUM(product_inventories.quantity_on_hand * products.unit_price) as total')
            ->value('total') ?? 0;

        // Low stock and out of stock counts
        $lowStockItems = ProductInventory::whereNull('product_variant_id')
            ->whereRaw('quantity_on_hand <= minimum_stock_level')
            ->whereNotNull('minimum_stock_level')
            ->count();

        $outOfStockItems = ProductInventory::whereNull('product_variant_id')
            ->where('quantity_on_hand', '<=', 0)
            ->count();

        // Recent movements count (last 7 days)
        $recentMovements = StockMovement::where('movement_date', '>=', now()->subDays(7))->count();

        // Movement type breakdown (last 30 days)
        $movementTypeBreakdown = StockMovement::where('movement_date', '>=', now()->subDays(30))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Stock status breakdown
        $statusBreakdown = [
            'in_stock' => ProductInventory::whereNull('product_variant_id')
                ->where('quantity_on_hand', '>', 0)
                ->whereRaw('quantity_on_hand > COALESCE(minimum_stock_level, 0)')
                ->count(),
            'low_stock' => $lowStockItems,
            'out_of_stock' => $outOfStockItems,
        ];

        return [
            'total_products' => $totalProducts,
            'total_variants' => $totalVariants,
            'total_stock_value' => $totalStockValue,
            'low_stock_items' => $lowStockItems,
            'out_of_stock_items' => $outOfStockItems,
            'recent_movements' => $recentMovements,
            'by_movement_type' => $movementTypeBreakdown,
            'by_status' => $statusBreakdown,
        ];
    }

    /**
     * Get recent stock movements.
     */
    private function getRecentMovements(array $filters = []): array
    {
        $query = StockMovement::with(['product:id,name,sku', 'user:id,name'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply product filter if specified
        if (! empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        $movements = $query->limit(10)->get();

        return $movements->map(function ($movement) {
            return [
                'id' => $movement->id,
                'movement_type' => $movement->movement_type,
                'quantity' => $movement->quantity,
                'reason' => $movement->reason,
                'quantity_before' => $movement->quantity_before,
                'quantity_after' => $movement->quantity_after,
                'movement_date' => $movement->movement_date->toISOString(),
                'created_at' => $movement->created_at->toISOString(),
                'product' => $movement->product ? [
                    'id' => $movement->product->id,
                    'name' => $movement->product->name,
                    'sku' => $movement->product->sku,
                ] : null,
                'user' => $movement->user ? [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Get products with low stock.
     */
    private function getLowStockProducts(): array
    {
        return Product::with(['inventory', 'category:id,name'])
            ->whereHas('inventory', function (Builder $q) {
                $q->whereRaw('quantity_on_hand <= minimum_stock_level')
                    ->whereNotNull('minimum_stock_level');
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->unit_price,
                    'reorder_level' => $product->reorder_level,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                    'inventory' => $product->inventory ? [
                        'quantity_on_hand' => $product->inventory->quantity_on_hand,
                        'quantity_available' => $product->inventory->quantity_available,
                        'minimum_stock_level' => $product->inventory->minimum_stock_level,
                        'maximum_stock_level' => $product->inventory->maximum_stock_level,
                    ] : null,
                ];
            })
            ->toArray();
    }
}
