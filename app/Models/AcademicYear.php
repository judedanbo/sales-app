<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class AcademicYear extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\AcademicYearFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'year_name',
        'start_date',
        'end_date',
        'is_current',
    ];

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'school_id',
        'year_name',
        'start_date',
        'end_date',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
