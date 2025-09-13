<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ProductPrice extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\ProductPriceFactory> */
    use AuditableTrait, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'version_number',
        'price',
        'final_price',
        'status',
        'valid_from',
        'valid_to',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'cost_price',
        'markup_percentage',
        'currency',
        'bulk_discounts',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'final_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'bulk_discounts' => 'array',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'product_id',
        'price',
        'final_price',
        'status',
        'valid_from',
        'valid_to',
        'approved_by',
        'approved_at',
    ];

    /**
     * Boot the model - auto-increment version number
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductPrice $productPrice) {
            if (empty($productPrice->version_number)) {
                $lastVersion = static::where('product_id', $productPrice->product_id)
                    ->max('version_number') ?? 0;
                $productPrice->version_number = $lastVersion + 1;
            }
        });
    }

    /**
     * Relationships
     */

    /**
     * Price belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who created this price
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this price
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */

    /**
     * Scope to get active prices
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope to get pending prices awaiting approval
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * Scope to get current valid prices
     */
    public function scopeCurrent(Builder $query): void
    {
        $now = now();
        $query->where('valid_from', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>', $now);
            });
    }

    /**
     * Scope to get prices for a specific product
     */
    public function scopeForProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * Get formatted final price
     */
    public function getFormattedFinalPriceAttribute(): string
    {
        return number_format($this->final_price, 2);
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if (! $this->cost_price || $this->cost_price == 0) {
            return 0;
        }

        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Check if price is currently valid
     */
    public function getIsValidAttribute(): bool
    {
        $now = now();

        return $this->valid_from <= $now &&
               ($this->valid_to === null || $this->valid_to > $now);
    }

    /**
     * Check if price is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'active' && ! is_null($this->approved_at);
    }

    /**
     * Helper Methods
     */

    /**
     * Approve this price version
     */
    public function approve(int $approverId, ?string $notes = null): bool
    {
        // Deactivate other active prices for the same product
        static::where('product_id', $this->product_id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        return $this->update([
            'status' => 'active',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Reject this price version
     */
    public function reject(int $approverId, string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approval_notes' => $reason,
        ]);
    }

    /**
     * Calculate final price based on base price and tax
     */
    public function calculateFinalPrice(): void
    {
        $taxRate = $this->product->tax_rate ?? 0;
        $this->final_price = $this->price * (1 + $taxRate);
    }

    /**
     * Get bulk discount for quantity
     */
    public function getBulkDiscountForQuantity(int $quantity): float
    {
        if (! $this->bulk_discounts) {
            return 0;
        }

        $applicableDiscount = 0;
        foreach ($this->bulk_discounts as $tier) {
            if ($quantity >= $tier['min_quantity']) {
                $applicableDiscount = $tier['discount_percentage'];
            }
        }

        return $applicableDiscount;
    }

    /**
     * Get effective price for quantity (with bulk discounts)
     */
    public function getEffectivePriceForQuantity(int $quantity): float
    {
        $discount = $this->getBulkDiscountForQuantity($quantity);

        return $this->final_price * (1 - $discount / 100);
    }
}
