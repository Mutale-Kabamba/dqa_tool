<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_code',
        'project_id',
        'site_name',
        'auditor_name',
        'audit_date',
        'period_month',
        'period_quarter',
        'period_year',
        'period_label',
        'overall_checked',
        'overall_compliant',
        'overall_score',
        'overall_status',
        'priority_areas',
        'root_cause_notes',
        'recommendations',
        'facility_in_charge',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'period_month' => 'integer',
        'period_quarter' => 'integer',
        'period_year' => 'integer',
        'overall_checked' => 'integer',
        'overall_compliant' => 'integer',
        'overall_score' => 'decimal:4',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dimensions(): HasMany
    {
        return $this->hasMany(AuditDimension::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(AuditActionItem::class);
    }

    public function scopeFiltered($query, ?array $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['period_year'])) {
            $query->where('period_year', $filters['period_year']);
        }

        if (!empty($filters['period_quarter'])) {
            $query->where('period_quarter', $filters['period_quarter']);
        }

        if (!empty($filters['period_month'])) {
            $query->where('period_month', $filters['period_month']);
        }

        return $query;
    }
}
