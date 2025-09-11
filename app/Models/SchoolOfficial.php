<?php

namespace App\Models;

use App\Enums\OfficialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SchoolOfficial extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\SchoolOfficialFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'user_id',
        'official_type',
        'name',
        'qualification',
        'department',
        'email',
        'phone',
        'is_primary',
    ];

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'school_id',
        'user_id',
        'official_type',
        'name',
        'qualification',
        'department',
        'email',
        'phone',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'official_type' => OfficialType::class,
            'is_primary' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
