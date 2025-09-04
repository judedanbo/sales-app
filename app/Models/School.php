<?php

namespace App\Models;

use App\Enums\BoardAffiliation;
use App\Enums\MediumOfInstruction;
use App\Enums\SchoolStatus;
use App\Enums\SchoolType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'school_type' => SchoolType::class,
            'board_affiliation' => BoardAffiliation::class,
            'medium_of_instruction' => MediumOfInstruction::class,
            'established_date' => 'date',
            'is_active' => SchoolStatus::class,
        ];
    }

    protected $fillable = [
        'school_code',
        'school_name',
        'school_type',
        'board_affiliation',
        'medium_of_instruction',
        'established_date',
        'principal_name',
        'total_students',
        'total_teachers',
        'website',
        'description',
        'is_active',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SchoolContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(SchoolAddress::class);
    }

    public function management(): HasMany
    {
        return $this->hasMany(SchoolManagement::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(SchoolOfficial::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SchoolDocument::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', SchoolStatus::Active);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('school_type', $type);
    }
}
