<?php

namespace App\Models;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolContact extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolContactFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'contact_type' => ContactType::class,
        ];
    }

    protected $fillable = [
        'school_id',
        'contact_type',
        'phone_primary',
        'phone_secondary',
        'email_primary',
        'email_secondary',
        'website',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
