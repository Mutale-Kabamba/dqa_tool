<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditActionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'dimension_name',
        'issue_description',
        'root_cause_category',
        'action_plan',
        'responsible_person',
        'due_date',
        'status',
        'resolution_notes',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status !== 'RESOLVED' && $this->due_date && $this->due_date->isPast()) {
            return 'OVERDUE';
        }
        return $this->status;
    }
}
