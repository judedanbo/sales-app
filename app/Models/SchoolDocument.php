<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SchoolDocument extends Model implements Auditable
{
    /** @use HasFactory<\Database\Factories\SchoolDocumentFactory> */
    use AuditableTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'document_type',
        'document_name',
        'document_number',
        'file_url',
        'issue_date',
        'expiry_date',
    ];

    /**
     * Audit configuration
     */
    protected $auditInclude = [
        'school_id',
        'document_type',
        'document_name',
        'document_number',
        'file_url',
        'issue_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'issue_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
