<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolAddress extends Model
{
    /** @use HasFactory<\Database\Factories\SchoolAddressFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'address_type' => AddressType::class,
        ];
    }

    protected $fillable = [
        'school_id',
        'address_type',
        'address_line1',
        'address_line2',
        'city',
        'state_province',
        'postal_code',
        'country',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
