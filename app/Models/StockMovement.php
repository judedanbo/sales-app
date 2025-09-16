<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class StockMovement extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use AuditableTrait, HasFactory;

    /**
     * Movement types
     */
    public const TYPE_INITIAL_STOCK = 'initial_stock';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_SALE = 'sale';

    public const TYPE_RETURN_FROM_CUSTOMER = 'return_from_customer';

    public const TYPE_RETURN_TO_SUPPLIER = 'return_to_supplier';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_DAMAGED = 'damaged';

    public const TYPE_EXPIRED = 'expired';

    public const TYPE_THEFT = 'theft';

    public const TYPE_MANUFACTURING = 'manufacturing';

    public const TYPE_RESERVATION = 'reservation';

    public const TYPE_RELEASE_RESERVATION = 'release_reservation';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'currency',
        'reference_type',
        'reference_id',
        'notes',
        'metadata',
        'location',
        'batch_number',
        'expiry_date',
        'user_id',
        'movement_date',
        'is_confirmed',
        'confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'quantity_change' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'metadata' => 'array',
            'expiry_date' => 'date',
            'movement_date' => 'datetime',
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'product_id',
        'product_variant_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'user_id',
        'movement_date',
        'is_confirmed',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (StockMovement $movement) {
            if (is_null($movement->movement_date)) {
                $movement->movement_date = now();
            }

            if ($movement->is_confirmed && is_null($movement->confirmed_at)) {
                $movement->confirmed_at = now();
            }

            // Auto-calculate total cost if unit cost is provided
            if ($movement->unit_cost && ! $movement->total_cost) {
                $movement->total_cost = abs($movement->quantity_change) * $movement->unit_cost;
            }
        });

        static::updating(function (StockMovement $movement) {
            if ($movement->isDirty('is_confirmed') && $movement->is_confirmed && is_null($movement->confirmed_at)) {
                $movement->confirmed_at = now();
            }
        });
    }

    /**
     * Relationships
     */

    /**
     * Movement belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Movement belongs to a product variant (optional)
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Movement belongs to a user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope to get movements by product
     */
    public function scopeForProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    /**
     * Scope to get movements by variant
     */
    public function scopeForVariant(Builder $query, int $variantId): void
    {
        $query->where('product_variant_id', $variantId);
    }

    /**
     * Scope to get movements by type
     */
    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope to get confirmed movements
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->where('is_confirmed', true);
    }

    /**
     * Scope to get unconfirmed movements
     */
    public function scopeUnconfirmed(Builder $query): void
    {
        $query->where('is_confirmed', false);
    }

    /**
     * Scope to get movements within date range
     */
    public function scopeBetweenDates(Builder $query, Carbon $from, Carbon $to): void
    {
        $query->whereBetween('movement_date', [$from, $to]);
    }

    /**
     * Scope to get movements in the last N days
     */
    public function scopeLastDays(Builder $query, int $days): void
    {
        $query->where('movement_date', '>=', now()->subDays($days));
    }

    /**
     * Scope to get inbound movements (positive quantity change)
     */
    public function scopeInbound(Builder $query): void
    {
        $query->where('quantity_change', '>', 0);
    }

    /**
     * Scope to get outbound movements (negative quantity change)
     */
    public function scopeOutbound(Builder $query): void
    {
        $query->where('quantity_change', '<', 0);
    }

    /**
     * Scope to get movements by location
     */
    public function scopeAtLocation(Builder $query, string $location): void
    {
        $query->where('location', $location);
    }

    /**
     * Scope to get movements with reference
     */
    public function scopeWithReference(Builder $query, string $referenceType, string $referenceId): void
    {
        $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get the movement direction (in/out/neutral)
     */
    public function getDirectionAttribute(): string
    {
        if ($this->quantity_change > 0) {
            return 'in';
        } elseif ($this->quantity_change < 0) {
            return 'out';
        }

        return 'neutral';
    }

    /**
     * Get the absolute quantity changed
     */
    public function getAbsoluteQuantityAttribute(): int
    {
        return abs($this->quantity_change);
    }

    /**
     * Get formatted movement type
     */
    public function getFormattedTypeAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->type));
    }

    /**
     * Get display name for the item moved
     */
    public function getItemDisplayNameAttribute(): string
    {
        if ($this->product_variant_id && $this->variant) {
            return $this->variant->display_name;
        }

        return $this->product->name ?? 'Unknown Product';
    }

    /**
     * Get SKU for the item moved
     */
    public function getItemSkuAttribute(): string
    {
        if ($this->product_variant_id && $this->variant) {
            return $this->variant->sku;
        }

        return $this->product->sku ?? 'N/A';
    }

    /**
     * Check if this is a variant movement
     */
    public function getIsVariantMovementAttribute(): bool
    {
        return ! is_null($this->product_variant_id);
    }

    /**
     * Get formatted unit cost
     */
    public function getFormattedUnitCostAttribute(): ?string
    {
        if (! $this->unit_cost) {
            return null;
        }

        return "{$this->currency} ".number_format($this->unit_cost, 2);
    }

    /**
     * Get formatted total cost
     */
    public function getFormattedTotalCostAttribute(): ?string
    {
        if (! $this->total_cost) {
            return null;
        }

        return "{$this->currency} ".number_format($this->total_cost, 2);
    }

    /**
     * Helper Methods
     */

    /**
     * Get all available movement types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_INITIAL_STOCK => 'Initial Stock',
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_RETURN_FROM_CUSTOMER => 'Return from Customer',
            self::TYPE_RETURN_TO_SUPPLIER => 'Return to Supplier',
            self::TYPE_ADJUSTMENT => 'Stock Adjustment',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_DAMAGED => 'Damaged',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_THEFT => 'Theft/Loss',
            self::TYPE_MANUFACTURING => 'Manufacturing',
            self::TYPE_RESERVATION => 'Reserved',
            self::TYPE_RELEASE_RESERVATION => 'Released Reservation',
        ];
    }

    /**
     * Get movement types that increase stock
     */
    public static function getInboundTypes(): array
    {
        return [
            self::TYPE_INITIAL_STOCK,
            self::TYPE_PURCHASE,
            self::TYPE_RETURN_FROM_CUSTOMER,
            self::TYPE_TRANSFER_IN,
            self::TYPE_MANUFACTURING,
            self::TYPE_RELEASE_RESERVATION,
        ];
    }

    /**
     * Get movement types that decrease stock
     */
    public static function getOutboundTypes(): array
    {
        return [
            self::TYPE_SALE,
            self::TYPE_RETURN_TO_SUPPLIER,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_DAMAGED,
            self::TYPE_EXPIRED,
            self::TYPE_THEFT,
            self::TYPE_RESERVATION,
        ];
    }

    /**
     * Confirm this movement
     */
    public function confirm(): bool
    {
        return $this->update([
            'is_confirmed' => true,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel/unconfirm this movement
     */
    public function cancel(): bool
    {
        return $this->update([
            'is_confirmed' => false,
            'confirmed_at' => null,
        ]);
    }

    /**
     * Create a stock movement and update inventory
     */
    public static function createMovement(array $data): self
    {
        $movement = static::create($data);

        // Update inventory if confirmed
        if ($movement->is_confirmed) {
            $movement->updateInventory();
        }

        return $movement;
    }

    /**
     * Update related inventory record
     */
    public function updateInventory(): bool
    {
        $inventory = ProductInventory::where('product_id', $this->product_id)
            ->where('product_variant_id', $this->product_variant_id)
            ->first();

        if (! $inventory) {
            // Create inventory record if it doesn't exist
            $inventory = ProductInventory::create([
                'product_id' => $this->product_id,
                'product_variant_id' => $this->product_variant_id,
                'quantity_on_hand' => max(0, $this->quantity_after),
                'quantity_available' => max(0, $this->quantity_after),
                'quantity_reserved' => 0,
                'last_movement_at' => $this->movement_date,
            ]);
        } else {
            // Update existing inventory
            $inventory->update([
                'quantity_on_hand' => max(0, $this->quantity_after),
                'last_movement_at' => $this->movement_date,
            ]);

            // Update available quantity (considering reservations)
            $inventory->updateAvailableQuantity();
        }

        return true;
    }
}
