<?php

namespace App\Models;

use App\Enums\ManagementType;
use App\Enums\OwnershipType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SchoolManagement extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\SchoolManagementFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'management_type' => ManagementType::class,
            'ownership_type' => OwnershipType::class,
        ];
    }

    protected $fillable = [
        'school_id',
        'management_type',
        'ownership_type',
        'managing_authority',
        'board_name',
    ];

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'school_id',
        'management_type',
        'ownership_type',
        'managing_authority',
        'board_name',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
