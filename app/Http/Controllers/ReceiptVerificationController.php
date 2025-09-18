<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptVerificationController extends Controller
{
    /**
     * Show receipt verification page
     */
    public function show(string $verificationToken): Response
    {
        $sale = Sale::where('verification_token', $verificationToken)
            ->with(['items.product', 'cashier:id,name', 'school:id,name'])
            ->first();

        if (!$sale) {
            abort(404, 'Receipt not found');
        }

        return $this->renderReceiptVerification($sale, $verificationToken);
    }

    /**
     * Show receipt verification page using sale number (fallback)
     */
    public function showBySaleNumber(string $saleIdentifier): Response
    {
        // Try to find by sale_number or ID
        $sale = Sale::where('sale_number', $saleIdentifier)
            ->orWhere('id', $saleIdentifier)
            ->with(['items.product', 'cashier:id,name', 'school:id,name'])
            ->first();

        if (!$sale) {
            abort(404, 'Receipt not found');
        }

        // Use sale_number as the identifier for fallback
        return $this->renderReceiptVerification($sale, $saleIdentifier, true);
    }

    /**
     * Render receipt verification page
     */
    private function renderReceiptVerification(Sale $sale, string $identifier, bool $isFallback = false): Response
    {
        // Transform sale data for receipt display
        $receiptData = [
            'sale_number' => $sale->sale_number,
            'receipt_number' => $sale->receipt_number ?? $sale->sale_number,
            'sale_date' => $sale->sale_date->format('M d, Y g:i A'),
            'cashier_name' => $sale->cashier->name ?? 'N/A',
            'customer_name' => $sale->customer_info['name'] ?? null,
            'payment_method' => $sale->payment_method,
            'subtotal' => $sale->subtotal,
            'tax_amount' => $sale->tax_amount,
            'total_amount' => $sale->total_amount,
            'status' => $sale->status,
            'items' => $sale->items->map(function ($item) {
                return [
                    'sku' => $item->product->sku ?? null,
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ];
            }),
            'business_name' => 'School Sales System', // You can make this configurable
            'business_address' => 'Your Business Address',
            'business_phone' => 'Your Business Phone',
            'verification_token' => $identifier,
            'is_fallback' => $isFallback,
        ];

        return Inertia::render('ReceiptVerification', [
            'receiptData' => $receiptData,
        ]);
    }
}