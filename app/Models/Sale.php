<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Sale extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_number',
        'sale_date',
        'status',
        'subtotal',
        'tax_amount',
        'total_amount',
        'payment_method',
        'customer_info',
        'cashier_id',
        'school_id',
        'notes',
        'receipt_number',
        'verification_token',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'customer_info' => 'json',
        'voided_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden from arrays.
     */
    protected $hidden = [];

    /**
     * Generate unique sale number
     */
    protected static function booted(): void
    {
        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                $sale->sale_number = static::generateSaleNumber();
            }
            if (empty($sale->sale_date)) {
                $sale->sale_date = now();
            }
            if (empty($sale->verification_token)) {
                $sale->verification_token = static::generateVerificationToken();
            }
        });
    }

    /**
     * Generate a unique sale number
     */
    public static function generateSaleNumber(): string
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');

        // Get the last sale number for today
        $lastSale = static::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastSale && preg_match('/(\d{4})$/', $lastSale->sale_number, $matches)) {
            $sequence = intval($matches[1]) + 1;
        }

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique verification token
     */
    public static function generateVerificationToken(): string
    {
        do {
            $token = Str::uuid()->toString();
        } while (static::where('verification_token', $token)->exists());

        return $token;
    }

    /**
     * Get the verification URL for this sale
     */
    public function getVerificationUrlAttribute(): string
    {
        // Use verification token if available
        if ($this->verification_token) {
            return url("/receipt/{$this->verification_token}");
        }

        // Fallback to sale number for temporary verification
        return url("/receipt/temp/{$this->sale_number}");
    }

    /**
     * Get verification token, generating one if missing
     */
    public function getVerificationTokenAttribute($value): ?string
    {
        // Return existing token if available
        if ($value) {
            return $value;
        }

        // Generate and save token if column exists but is null
        if ($this->hasAttributeColumn('verification_token') && !$value) {
            $token = static::generateVerificationToken();

            // Try to save the token (will fail gracefully if column doesn't exist)
            try {
                $this->update(['verification_token' => $token]);
                return $token;
            } catch (\Exception $e) {
                // Column might not exist yet, return null
                return null;
            }
        }

        return null;
    }

    /**
     * Check if model has a specific attribute (column exists)
     */
    private function hasAttributeColumn(string $attribute): bool
    {
        return array_key_exists($attribute, $this->attributes) ||
               $this->hasGetMutator($attribute) ||
               $this->hasAttributeMutator($attribute) ||
               in_array($attribute, $this->fillable);
    }

    /**
     * Get the cashier that processed this sale.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the school associated with this sale.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who voided this sale.
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Get the user who created this sale.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this sale.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the sale items for this sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Check if sale is voided
     */
    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    /**
     * Check if sale is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if sale is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Void this sale
     */
    public function void(User $user, ?string $reason = null): bool
    {
        return DB::transaction(function () use ($user, $reason) {
            $this->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => $user->id,
                'void_reason' => $reason,
                'updated_by' => $user->id,
            ]);

            // Restore inventory for all items
            foreach ($this->items as $item) {
                if ($item->inventory_updated) {
                    $item->restoreInventory();
                }
            }

            return true;
        });
    }

    /**
     * Calculate totals based on items
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('line_total');
        $taxAmount = $this->items->sum('tax_amount');

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount,
        ]);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sale_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by cashier
     */
    public function scopeByCashier($query, int $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    /**
     * Scope to filter by school
     */
    public function scopeBySchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
