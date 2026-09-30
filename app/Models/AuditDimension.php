<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditDimension extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'dimension_name',
        'checked_count',
        'compliant_count',
        'score_percentage',
        'status',
    ];

    protected $casts = [
        'checked_count' => 'integer',
        'compliant_count' => 'integer',
        'score_percentage' => 'decimal:4',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
