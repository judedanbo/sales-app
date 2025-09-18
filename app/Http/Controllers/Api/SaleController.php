<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of sales.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeViewAny(Sale::class);

        $query = Sale::with(['cashier', 'school', 'items.product', 'voidedBy'])
            ->withCount(['items']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('receipt_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('cashier', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('cashier_id')) {
            $query->byCashier($request->cashier_id);
        }

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        } elseif ($request->filled('date_from')) {
            $query->where('sale_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('sale_date', '<=', $request->date_to);
        }

        // Default to recent sales first
        $query->orderBy('sale_date', 'desc');

        $sales = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $sales->items(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Store a newly created sale.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeCreate(Sale::class);

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money,bank_transfer,other',
            'customer_info' => 'nullable|array',
            'customer_info.name' => 'nullable|string|max:255',
            'customer_info.phone' => 'nullable|string|max:20',
            'customer_info.email' => 'nullable|email|max:255',
            'school_id' => 'nullable|exists:schools,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the sale
            $sale = Sale::create([
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'customer_info' => $request->customer_info,
                'cashier_id' => Auth::id(),
                'school_id' => $request->school_id,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            // Create sale items
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                if (!$product) {
                    throw new \Exception("Product not found: {$itemData['product_id']}");
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'tax_rate' => $product->tax_rate,
                ]);
            }

            // Calculate totals
            $sale->calculateTotals();

            // Complete the sale
            $sale->update([
                'status' => 'completed',
                'receipt_number' => 'REC' . $sale->sale_number,
            ]);

            DB::commit();

            // Load relationships for response
            $sale->load(['cashier', 'school', 'items.product']);

            return response()->json([
                'message' => 'Sale created successfully',
                'data' => $sale,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create sale',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): JsonResponse
    {
        $this->authorizeView($sale);

        $sale->load([
            'cashier',
            'school',
            'items.product.category',
            'voidedBy',
            'creator',
            'updater'
        ]);

        return response()->json([
            'data' => $sale,
        ]);
    }

    /**
     * Update the specified sale (limited fields).
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeUpdate($sale);

        // Only allow updating certain fields for completed sales
        $validator = Validator::make($request->all(), [
            'customer_info' => 'nullable|array',
            'customer_info.name' => 'nullable|string|max:255',
            'customer_info.phone' => 'nullable|string|max:20',
            'customer_info.email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($sale->isVoided()) {
            return response()->json([
                'message' => 'Cannot update voided sale',
            ], 400);
        }

        $sale->update([
            'customer_info' => $request->customer_info,
            'notes' => $request->notes,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Sale updated successfully',
            'data' => $sale,
        ]);
    }

    /**
     * Void the specified sale.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $this->authorizeDelete($sale);

        if ($sale->isVoided()) {
            return response()->json([
                'message' => 'Sale is already voided',
            ], 400);
        }

        $sale->void(Auth::user(), 'Voided via API');

        return response()->json([
            'message' => 'Sale voided successfully',
        ]);
    }

    /**
     * Void a sale with reason.
     */
    public function void(Request $request, Sale $sale): JsonResponse
    {
        $this->authorizeDelete($sale);

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($sale->isVoided()) {
            return response()->json([
                'message' => 'Sale is already voided',
            ], 400);
        }

        $sale->void(Auth::user(), $request->reason);

        return response()->json([
            'message' => 'Sale voided successfully',
        ]);
    }

    /**
     * Get receipt data for a sale.
     */
    public function receipt(Sale $sale): JsonResponse
    {
        $this->authorizeView($sale);

        $sale->load([
            'cashier',
            'school',
            'items.product'
        ]);

        return response()->json([
            'data' => [
                'sale' => $sale,
                'receipt_data' => [
                    'business_name' => config('app.name'),
                    'address' => 'Your Business Address',
                    'phone' => 'Your Phone Number',
                    'email' => 'your@email.com',
                    'generated_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Get sales statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $baseQuery = Sale::query();

        // Apply date filter if provided
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $baseQuery->byDateRange($request->date_from, $request->date_to);
        }

        // Use separate queries from the base query for accurate calculations
        $totalSales = (clone $baseQuery)->count();
        $completedSales = (clone $baseQuery)->byStatus('completed')->count();
        $voidedSales = (clone $baseQuery)->byStatus('voided')->count();

        // Only include completed sales in revenue calculations
        $totalRevenue = (clone $baseQuery)->byStatus('completed')->sum('total_amount');
        $averageOrderValue = $completedSales > 0 ?
            (clone $baseQuery)->byStatus('completed')->avg('total_amount') : 0;

        // Top products
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.line_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'total_revenue_formatted' => number_format($totalRevenue, 2),
                'completed_sales' => $completedSales,
                'voided_sales' => $voidedSales,
                'average_order_value' => $averageOrderValue,
                'average_order_value_formatted' => number_format($averageOrderValue, 2),
                'completion_rate' => $totalSales > 0 ?
                    round(($completedSales / $totalSales) * 100, 1) : 0,
                'completion_rate_formatted' => $totalSales > 0 ?
                    number_format(($completedSales / $totalSales) * 100, 1) . '%' : '0%',
                'top_products' => $topProducts,
            ],
        ]);
    }

    /**
     * Get daily sales summary.
     */
    public function dailySummary(Request $request): JsonResponse
    {
        $date = $request->get('date', now()->toDateString());
        $cashierId = $request->get('cashier_id', Auth::id());

        $sales = Sale::whereDate('sale_date', $date)
            ->when($cashierId, function ($query, $cashierId) {
                return $query->where('cashier_id', $cashierId);
            })
            ->get();

        $summary = [
            'date' => $date,
            'cashier_id' => $cashierId,
            'total_transactions' => $sales->count(),
            'completed_transactions' => $sales->where('status', 'completed')->count(),
            'voided_transactions' => $sales->where('status', 'voided')->count(),
            'total_revenue' => $sales->where('status', 'completed')->sum('total_amount'),
            'total_tax' => $sales->where('status', 'completed')->sum('tax_amount'),
            'payment_methods' => $sales->where('status', 'completed')
                ->groupBy('payment_method')
                ->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'total' => $group->sum('total_amount'),
                    ];
                }),
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }
}
