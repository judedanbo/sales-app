<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sku',
        'name',
        'description',
        'category_id',
        'status',
        'unit_price',
        'unit_type',
        'reorder_level',
        'tax_rate',
        'weight',
        'dimensions',
        'color',
        'brand',
        'attributes',
        'barcode',
        'image_url',
        'gallery',
        'meta_title',
        'meta_description',
        'tags',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'weight' => 'decimal:3',
            'dimensions' => 'array',
            'attributes' => 'array',
            'gallery' => 'array',
            'tags' => 'array',
            'reorder_level' => 'integer',
        ];
    }

    /**
     * Audit configuration - include all important fields
     */
    protected $auditInclude = [
        'sku',
        'name',
        'description',
        'category_id',
        'status',
        'unit_price',
        'unit_type',
        'reorder_level',
        'tax_rate',
        'brand',
        'barcode',
        'updated_by',
    ];

    /**
     * Boot the model - generate SKU if not provided
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Product $product) {
            if (empty($product->sku)) {
                $product->sku = static::generateSku($product->name);
            }
        });
    }

    /**
     * Relationships
     */

    /**
     * Product belongs to a category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Product has many price versions (for price history)
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->orderBy('created_at', 'desc');
    }

    /**
     * Current active price version
     */
    public function currentPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class)
            ->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where(function ($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>', now());
            })
            ->latest('valid_from');
    }

    /**
     * Product has many class requirements (school integration)
     */
    public function classRequirements(): HasMany
    {
        return $this->hasMany(ClassProductRequirement::class);
    }

    /**
     * Product has inventory records
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(ProductInventory::class);
    }

    /**
     * User who created this product
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this product
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */

    /**
     * Scope to get only active products
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Scope to get inactive products
     */
    public function scopeInactive(Builder $query): void
    {
        $query->where('status', 'inactive');
    }

    /**
     * Scope to get discontinued products
     */
    public function scopeDiscontinued(Builder $query): void
    {
        $query->where('status', 'discontinued');
    }

    /**
     * Scope to get products by category
     */
    public function scopeByCategory(Builder $query, int $categoryId): void
    {
        $query->where('category_id', $categoryId);
    }

    /**
     * Scope to get low stock products
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->whereHas('inventory', function ($q) {
            $q->whereRaw('quantity_on_hand <= minimum_stock_level');
        });
    }

    /**
     * Scope to search products by name, description, or SKU
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('sku', 'LIKE', "%{$search}%")
                ->orWhere('brand', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get formatted price with tax in Ghana Cedis (GHS)
     */
    public function getFormattedPriceAttribute(): string
    {
        $priceWithTax = $this->unit_price * (1 + $this->tax_rate);

        return 'GH₵ '.number_format($priceWithTax, 2);
    }

    /**
     * Get formatted price without tax in Ghana Cedis (GHS)
     */
    public function getFormattedBasePriceAttribute(): string
    {
        return 'GH₵ '.number_format($this->unit_price, 2);
    }

    /**
     * Get display name with SKU
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->sku})";
    }

    /**
     * Check if product is available for sale
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get current stock level
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->inventory?->quantity ?? 0;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        if (! $this->reorder_level || ! $this->inventory) {
            return false;
        }

        return $this->inventory->quantity_on_hand <= $this->reorder_level;
    }

    /**
     * Check if stock is low (alias for Vue components)
     */
    public function getLowStockAttribute(): bool
    {
        return $this->getIsLowStockAttribute();
    }

    /**
     * Helper Methods
     */

    /**
     * Generate unique SKU based on product name
     */
    public static function generateSku(string $name, int $attempt = 1): string
    {
        $baseSku = strtoupper(Str::slug($name, ''));
        $baseSku = substr($baseSku, 0, 8); // Limit to 8 characters

        $sku = $attempt === 1 ? $baseSku : $baseSku.$attempt;

        // Check if SKU already exists
        if (static::where('sku', $sku)->exists()) {
            return static::generateSku($name, $attempt + 1);
        }

        return $sku;
    }

    /**
     * Update the product's updated_by field
     */
    public function updateUpdatedBy(int $userId): bool
    {
        return $this->update(['updated_by' => $userId]);
    }

    /**
     * Check if user can manage this product based on category permissions
     */
    public function canBeManaged(): bool
    {
        // This would be expanded based on specific business rules
        // For now, active products can be managed
        return $this->status !== 'discontinued';
    }

    /**
     * Get products requiring reorder
     */
    public static function getProductsNeedingReorder()
    {
        return static::with(['inventory', 'category'])
            ->whereHas('inventory', function ($query) {
                $query->whereRaw('quantity_on_hand <= minimum_stock_level');
            })
            ->where('status', 'active')
            ->get();
    }
}
