<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    use AuthorizesResourceOperations;
    /**
     * Display the POS interface.
     */
    public function pos(): Response
    {
        // Load active products with inventory for POS
        $products = Product::with(['inventory', 'category'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->unit_price,
                    'tax_rate' => $product->tax_rate,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                    'inventory' => $product->inventory ? [
                        'quantity_available' => $product->inventory->quantity_available,
                        'quantity_on_hand' => $product->inventory->quantity_on_hand,
                    ] : null,
                ];
            });

        return Inertia::render('Sales/POS', [
            'products' => $products,
        ]);
    }

    /**
     * Display a listing of sales.
     */
    public function index(): Response
    {
        return Inertia::render('Sales/Index');
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): Response
    {
        $sale->load([
            'cashier',
            'school',
            'items.product.category',
            'voidedBy',
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * Store a newly created sale.
     */
    public function store(Request $request): RedirectResponse
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
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Pre-validate stock availability for all items
            foreach ($request->items as $itemData) {
                $product = Product::with('inventory')->find($itemData['product_id']);
                if (!$product) {
                    throw new \Exception("Product not found: {$itemData['product_id']}");
                }

                $availableStock = $product->inventory?->quantity_available ?? 0;
                $requestedQuantity = $itemData['quantity'];

                if ($availableStock < $requestedQuantity) {
                    throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$availableStock}, Requested: {$requestedQuantity}");
                }
            }

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

            // Create sale items (stock will be automatically adjusted by SaleItem model)
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);

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

            // Load the sale with relationships for receipt printing
            $sale->load([
                'cashier',
                'items.product',
                'school'
            ]);

            return back()
                ->with('success', "Sale processed successfully! Sale #: {$sale->sale_number}")
                ->with('flash.sale', $sale->toArray());

        } catch (\Exception $e) {
            DB::rollBack();

            // Check if it's a stock-related error
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                return back()
                    ->withErrors(['stock_error' => $e->getMessage()])
                    ->withInput();
            }

            // Generic error handling
            return back()
                ->withErrors(['error' => 'Failed to process sale: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Void the specified sale.
     */
    public function void(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('void', $sale);

        $validator = Validator::make($request->all(), [
            'void_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($sale->status === 'voided') {
            return back()->withErrors(['error' => 'Sale is already voided.']);
        }

        if ($sale->status !== 'completed') {
            return back()->withErrors(['error' => 'Only completed sales can be voided.']);
        }

        DB::beginTransaction();

        try {
            // Update sale status
            $sale->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $request->void_reason,
                'updated_by' => auth()->id(),
            ]);

            // Restore inventory for each sale item
            foreach ($sale->items as $item) {
                $item->restoreInventory();
            }

            DB::commit();

            return back()->with('success', "Sale {$sale->sale_number} has been voided successfully.");

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Failed to void sale: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
