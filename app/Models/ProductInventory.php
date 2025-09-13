<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ProductInventory extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\ProductInventoryFactory> */
    use AuditableTrait, HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'product_inventories';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'quantity_on_hand',
        'quantity_available',
        'quantity_reserved',
        'minimum_stock_level',
        'maximum_stock_level',
        'reorder_point',
        'reorder_quantity',
        'last_stock_count',
        'last_movement_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_available' => 'integer',
            'quantity_reserved' => 'integer',
            'minimum_stock_level' => 'integer',
            'maximum_stock_level' => 'integer',
            'reorder_point' => 'integer',
            'reorder_quantity' => 'integer',
            'last_stock_count' => 'datetime',
            'last_movement_at' => 'datetime',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'quantity_on_hand',
        'quantity_available',
        'quantity_reserved',
        'minimum_stock_level',
        'maximum_stock_level',
        'reorder_point',
        'reorder_quantity',
    ];

    /**
     * Relationships
     */

    /**
     * Product inventory belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessors
     */

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        if (! $this->minimum_stock_level) {
            return false;
        }

        return $this->quantity_on_hand <= $this->minimum_stock_level;
    }

    /**
     * Check if out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_on_hand <= 0;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return 'out_of_stock';
        } elseif ($this->is_low_stock) {
            return 'low_stock';
        } elseif ($this->reorder_point && $this->quantity_on_hand <= $this->reorder_point) {
            return 'reorder_needed';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Helper Methods
     */

    /**
     * Update stock levels after movement
     */
    public function updateStockMovement(int $quantityChange, string $movementType = 'adjustment'): void
    {
        $this->quantity_on_hand += $quantityChange;
        $this->last_movement_at = now();
        $this->save();
    }

    /**
     * Reserve quantity for orders
     */
    public function reserveQuantity(int $quantity): bool
    {
        if ($this->quantity_available < $quantity) {
            return false;
        }

        $this->quantity_available -= $quantity;
        $this->quantity_reserved += $quantity;
        $this->save();

        return true;
    }

    /**
     * Release reserved quantity
     */
    public function releaseReservedQuantity(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_available += $quantity;
        $this->save();

        return true;
    }

    /**
     * Fulfill reserved quantity (remove from inventory)
     */
    public function fulfillReservedQuantity(int $quantity): bool
    {
        if ($this->quantity_reserved < $quantity) {
            return false;
        }

        $this->quantity_reserved -= $quantity;
        $this->quantity_on_hand -= $quantity;
        $this->last_movement_at = now();
        $this->save();

        return true;
    }
}
