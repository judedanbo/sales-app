<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ProductVariant extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use AuditableTrait, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'size',
        'color',
        'material',
        'attributes',
        'unit_price',
        'cost_price',
        'weight',
        'dimensions',
        'image_url',
        'gallery',
        'status',
        'is_default',
        'sort_order',
        'barcode',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'dimensions' => 'array',
            'gallery' => 'array',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'weight' => 'decimal:3',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'product_id',
        'sku',
        'name',
        'size',
        'color',
        'material',
        'unit_price',
        'cost_price',
        'status',
        'is_default',
        'updated_by',
    ];

    /**
     * Boot the model - generate SKU if not provided
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductVariant $variant) {
            if (empty($variant->sku)) {
                $variant->sku = static::generateVariantSku($variant);
            }

            // Auto-set sort order
            if ($variant->sort_order === 0) {
                $lastOrder = static::where('product_id', $variant->product_id)->max('sort_order') ?? 0;
                $variant->sort_order = $lastOrder + 1;
            }
        });

        // Ensure only one default variant per product
        static::saving(function (ProductVariant $variant) {
            if ($variant->is_default) {
                static::where('product_id', $variant->product_id)
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Relationships
     */

    /**
     * Variant belongs to a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant has inventory records
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    /**
     * User who created this variant
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this variant
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Variant has many stock movements
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_variant_id')->orderBy('movement_date', 'desc');
    }

    /**
     * Variant has many recent stock movements (last 30 days)
     */
    public function recentStockMovements(): HasMany
    {
        return $this->stockMovements()
            ->where('movement_date', '>=', now()->subDays(30));
    }

    /**
     * Scopes
     */

    /**
     * Scope to get only active variants
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope to get inactive variants
     */
    public function scopeInactive(Builder $query): void
    {
        $query->where('status', 'inactive');
    }

    /**
     * Scope to get discontinued variants
     */
    public function scopeDiscontinued(Builder $query): void
    {
        $query->where('status', 'discontinued');
    }

    /**
     * Scope to get default variants
     */
    public function scopeDefault(Builder $query): void
    {
        $query->where('is_default', true);
    }

    /**
     * Scope to get variants by size
     */
    public function scopeBySize(Builder $query, string $size): void
    {
        $query->where('size', $size);
    }

    /**
     * Scope to get variants by color
     */
    public function scopeByColor(Builder $query, string $color): void
    {
        $query->where('color', $color);
    }

    /**
     * Scope to get variants by material
     */
    public function scopeByMaterial(Builder $query, string $material): void
    {
        $query->where('material', $material);
    }

    /**
     * Scope to get variants ordered by sort order
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order');
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get the effective price (variant price or product price)
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->unit_price ?? $this->product->unit_price;
    }

    /**
     * Get formatted effective price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'GH₵ '.number_format($this->effective_price, 2);
    }

    /**
     * Get display name with variant attributes
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->product->name];

        if ($this->size) {
            $parts[] = $this->size;
        }
        if ($this->color) {
            $parts[] = $this->color;
        }
        if ($this->material) {
            $parts[] = $this->material;
        }

        return implode(' - ', $parts);
    }

    /**
     * Get variant attributes as a formatted string
     */
    public function getVariantAttributesAttribute(): string
    {
        $attributes = [];

        if ($this->size) {
            $attributes[] = "Size: {$this->size}";
        }
        if ($this->color) {
            $attributes[] = "Color: {$this->color}";
        }
        if ($this->material) {
            $attributes[] = "Material: {$this->material}";
        }

        return implode(', ', $attributes);
    }

    /**
     * Check if variant has inventory
     */
    public function getHasInventoryAttribute(): bool
    {
        return ! is_null($this->inventory);
    }

    /**
     * Get current stock level
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->inventory?->quantity_on_hand ?? 0;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        if (! $this->inventory || ! $this->inventory->minimum_stock_level) {
            return false;
        }

        return $this->inventory->quantity_on_hand <= $this->inventory->minimum_stock_level;
    }

    /**
     * Check if variant is available for sale
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'active' && $this->current_stock > 0;
    }

    /**
     * Helper Methods
     */

    /**
     * Generate unique SKU for variant
     */
    public static function generateVariantSku(ProductVariant $variant, int $attempt = 1): string
    {
        $product = $variant->product;
        $baseSku = $product->sku;

        $variantParts = [];
        if ($variant->size) {
            $variantParts[] = strtoupper(substr($variant->size, 0, 1));
        }
        if ($variant->color) {
            $variantParts[] = strtoupper(substr($variant->color, 0, 2));
        }
        if ($variant->material) {
            $variantParts[] = strtoupper(substr($variant->material, 0, 2));
        }

        $variantCode = implode('', $variantParts);
        $sku = $baseSku.'-'.($variantCode ?: 'VAR');

        if ($attempt > 1) {
            $sku .= $attempt;
        }

        // Check if SKU already exists
        if (static::where('sku', $sku)->exists()) {
            return static::generateVariantSku($variant, $attempt + 1);
        }

        return $sku;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if (! $this->cost_price || $this->cost_price == 0) {
            return 0;
        }

        $effectivePrice = $this->effective_price;

        return (($effectivePrice - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Update the variant's updated_by field
     */
    public function updateUpdatedBy(int $userId): bool
    {
        return $this->update(['updated_by' => $userId]);
    }
}
