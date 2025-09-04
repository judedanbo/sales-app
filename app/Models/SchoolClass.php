<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolClassFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'class_name',
        'class_code',
        'grade_level',
        'min_age',
        'max_age',
        'order_sequence',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'min_age' => 'integer',
            'max_age' => 'integer',
            'order_sequence' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
