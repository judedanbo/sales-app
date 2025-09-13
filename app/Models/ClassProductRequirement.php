<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ClassProductRequirement extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\ClassProductRequirementFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'product_id',
        'is_required',
        'min_quantity',
        'max_quantity',
        'recommended_quantity',
        'required_by',
        'is_active',
        'description',
        'notes',
        'estimated_cost',
        'budget_allocation',
        'priority',
        'requirement_category',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'recommended_quantity' => 'integer',
            'estimated_cost' => 'decimal:2',
            'budget_allocation' => 'decimal:2',
            'required_by' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'school_id',
        'academic_year_id',
        'class_id',
        'product_id',
        'is_required',
        'min_quantity',
        'max_quantity',
        'is_active',
        'priority',
        'approved_by',
        'approved_at',
    ];

    /**
     * Relationships
     */

    /**
     * Requirement belongs to a school
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Requirement belongs to an academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Requirement belongs to a school class
     */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Requirement is for a product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who created this requirement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this requirement
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scopes
     */

    /**
     * Scope to get only required products
     */
    public function scopeRequired(Builder $query): void
    {
        $query->where('is_required', true);
    }

    /**
     * Scope to get optional products
     */
    public function scopeOptional(Builder $query): void
    {
        $query->where('is_required', false);
    }

    /**
     * Scope to get active requirements
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope to get requirements for a specific school
     */
    public function scopeForSchool(Builder $query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /**
     * Scope to get requirements for a specific academic year
     */
    public function scopeForAcademicYear(Builder $query, int $academicYearId): void
    {
        $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope to get requirements for a specific class
     */
    public function scopeForClass(Builder $query, int $classId): void
    {
        $query->where('class_id', $classId);
    }

    /**
     * Scope to get requirements by priority
     */
    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
    }

    /**
     * Scope to get requirements by category
     */
    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('requirement_category', $category);
    }

    /**
     * Scope to get requirements with upcoming deadlines
     */
    public function scopeUpcoming(Builder $query, int $days = 30): void
    {
        $query->whereNotNull('required_by')
            ->where('required_by', '<=', now()->addDays($days))
            ->where('required_by', '>=', now());
    }

    /**
     * Scope to get overdue requirements
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotNull('required_by')
            ->where('required_by', '<', now())
            ->where('is_active', true);
    }

    /**
     * Accessors & Mutators
     */

    /**
     * Get formatted estimated cost
     */
    public function getFormattedEstimatedCostAttribute(): string
    {
        return $this->estimated_cost ? number_format($this->estimated_cost, 2) : '0.00';
    }

    /**
     * Get formatted budget allocation
     */
    public function getFormattedBudgetAllocationAttribute(): string
    {
        return $this->budget_allocation ? number_format($this->budget_allocation, 2) : '0.00';
    }

    /**
     * Get total estimated cost for recommended quantity
     */
    public function getTotalEstimatedCostAttribute(): float
    {
        $quantity = $this->recommended_quantity ?: $this->min_quantity;

        return ($this->estimated_cost ?: 0) * $quantity;
    }

    /**
     * Check if requirement is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->required_by && $this->required_by < now() && $this->is_active;
    }

    /**
     * Check if requirement is upcoming (within 30 days)
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->required_by &&
               $this->required_by >= now() &&
               $this->required_by <= now()->addDays(30);
    }

    /**
     * Check if requirement is approved
     */
    public function getIsApprovedAttribute(): bool
    {
        return ! is_null($this->approved_at) && ! is_null($this->approved_by);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray'
        };
    }

    /**
     * Helper Methods
     */

    /**
     * Approve this requirement
     */
    public function approve(int $approverId): bool
    {
        return $this->update([
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Activate this requirement
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate this requirement
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Copy requirements from one academic year to another
     */
    public static function copyRequirementsToNewYear(int $fromAcademicYearId, int $toAcademicYearId, int $schoolId): int
    {
        $requirements = static::where('academic_year_id', $fromAcademicYearId)
            ->where('school_id', $schoolId)
            ->get();

        $copiedCount = 0;

        foreach ($requirements as $requirement) {
            $newRequirement = $requirement->replicate();
            $newRequirement->academic_year_id = $toAcademicYearId;
            $newRequirement->approved_by = null;
            $newRequirement->approved_at = null;
            $newRequirement->created_at = now();
            $newRequirement->updated_at = now();

            if ($newRequirement->save()) {
                $copiedCount++;
            }
        }

        return $copiedCount;
    }

    /**
     * Get requirements summary for a class
     */
    public static function getClassRequirementsSummary(int $schoolId, int $academicYearId, int $classId): array
    {
        $requirements = static::with('product')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->get();

        return [
            'total_requirements' => $requirements->count(),
            'required_items' => $requirements->where('is_required', true)->count(),
            'optional_items' => $requirements->where('is_required', false)->count(),
            'total_estimated_cost' => $requirements->sum('total_estimated_cost'),
            'critical_items' => $requirements->where('priority', 'critical')->count(),
            'overdue_items' => $requirements->where('is_overdue', true)->count(),
        ];
    }
}
