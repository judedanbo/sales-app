<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SaleItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
        'tax_rate',
        'tax_amount',
        'product_snapshot',
        'inventory_updated',
        'inventory_updated_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'product_snapshot' => 'json',
        'inventory_updated' => 'boolean',
        'inventory_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($saleItem) {
            if (empty($saleItem->product_snapshot)) {
                $saleItem->captureProductSnapshot();
            }
            $saleItem->calculateLineTotals();
        });

        static::updating(function ($saleItem) {
            $saleItem->calculateLineTotals();
        });

        static::created(function ($saleItem) {
            $saleItem->updateInventory();
        });
    }

    /**
     * Get the sale that owns this item.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product for this sale item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Capture product snapshot for historical accuracy.
     */
    public function captureProductSnapshot(): void
    {
        if ($this->product) {
            $this->product_snapshot = [
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'category' => $this->product->category?->name,
                'unit_type' => $this->product->unit_type,
                'tax_rate' => $this->product->tax_rate,
                'brand' => $this->product->brand,
                'barcode' => $this->product->barcode,
                'captured_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Calculate line totals based on quantity and unit price.
     */
    public function calculateLineTotals(): void
    {
        $this->line_total = $this->quantity * $this->unit_price;
        $this->tax_amount = $this->line_total * $this->tax_rate;
    }

    /**
     * Update inventory for this sale item.
     */
    public function updateInventory(): void
    {
        if ($this->inventory_updated || !$this->product) {
            return;
        }

        DB::transaction(function () {
            $inventory = $this->product->inventory;
            if ($inventory) {
                // Check if we have enough stock
                if ($inventory->quantity_available < $this->quantity) {
                    throw new \Exception("Insufficient stock for product: {$this->product->name}. Available: {$inventory->quantity_available}, Requested: {$this->quantity}");
                }

                // Deduct from inventory
                $inventory->update([
                    'quantity_on_hand' => $inventory->quantity_on_hand - $this->quantity,
                    'quantity_available' => $inventory->quantity_available - $this->quantity,
                    'last_movement_at' => now(),
                ]);

                // Create stock movement record
                StockMovement::create([
                    'product_id' => $this->product_id,
                    'type' => 'sale',
                    'quantity_change' => -$this->quantity, // Negative for outgoing
                    'quantity_before' => $inventory->quantity_on_hand,
                    'quantity_after' => $inventory->quantity_on_hand - $this->quantity,
                    'reference_id' => $this->sale_id,
                    'reference_type' => 'sale',
                    'notes' => "Sale: {$this->sale->sale_number}",
                    'user_id' => $this->sale->cashier_id,
                ]);

                // Mark inventory as updated
                $this->update([
                    'inventory_updated' => true,
                    'inventory_updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Restore inventory when sale is voided.
     */
    public function restoreInventory(): void
    {
        if (!$this->inventory_updated || !$this->product) {
            return;
        }

        DB::transaction(function () {
            $inventory = $this->product->inventory;
            if ($inventory) {
                // Add back to inventory
                $inventory->update([
                    'quantity_on_hand' => $inventory->quantity_on_hand + $this->quantity,
                    'quantity_available' => $inventory->quantity_available + $this->quantity,
                    'last_movement_at' => now(),
                ]);

                // Create stock movement record for void
                StockMovement::create([
                    'product_id' => $this->product_id,
                    'type' => 'adjustment',
                    'quantity_change' => $this->quantity, // Positive for incoming
                    'quantity_before' => $inventory->quantity_on_hand,
                    'quantity_after' => $inventory->quantity_on_hand + $this->quantity,
                    'reference_id' => $this->sale_id,
                    'reference_type' => 'sale_void',
                    'notes' => "Void sale: {$this->sale->sale_number}",
                    'user_id' => $this->sale->voided_by,
                ]);

                // Mark inventory as not updated
                $this->update([
                    'inventory_updated' => false,
                    'inventory_updated_at' => null,
                ]);
            }
        });
    }

    /**
     * Get the product name from snapshot or current product.
     */
    public function getProductNameAttribute(): string
    {
        return $this->product_snapshot['name'] ?? $this->product?->name ?? 'Unknown Product';
    }

    /**
     * Get the product SKU from snapshot or current product.
     */
    public function getProductSkuAttribute(): string
    {
        return $this->product_snapshot['sku'] ?? $this->product?->sku ?? 'N/A';
    }

    /**
     * Get the total with tax included.
     */
    public function getTotalWithTaxAttribute(): float
    {
        return $this->line_total + $this->tax_amount;
    }
}
