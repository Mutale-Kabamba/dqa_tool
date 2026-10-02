<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    // 5-Step Operational Pipeline Statuses
    public const STATUS_PENDING_ASSIGNMENT = 'PENDING_ASSIGNMENT';
    public const STATUS_ASSIGNED_TO_AUDITOR = 'ASSIGNED_TO_AUDITOR';
    public const STATUS_AUDIT_COMPLETED = 'AUDIT_COMPLETED';
    public const STATUS_CAPA_SUBMITTED = 'CAPA_SUBMITTED';
    public const STATUS_AUDIT_CLOSED = 'AUDIT_CLOSED';

    protected $fillable = [
        'audit_code',
        'project_id',
        'auditor_id',
        'project_officer_id',
        'workflow_status',
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
        'strengths_notes',
        'discrepancies_notes',
        'recommendations',
        'facility_in_charge',
        'data_file_path',
        'certified_by_id',
        'certified_at',
        'closure_notes',
    ];

    protected $casts = [
        'audit_date' => 'date',
        'period_month' => 'integer',
        'period_quarter' => 'integer',
        'period_year' => 'integer',
        'overall_checked' => 'integer',
        'overall_compliant' => 'integer',
        'overall_score' => 'decimal:4',
        'certified_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auditor_id');
    }

    public function projectOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_officer_id');
    }

    public function certifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'certified_by_id');
    }

    public function dimensions(): HasMany
    {
        return $this->hasMany(AuditDimension::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(AuditActionItem::class);
    }

    public function isPendingAssignment(): bool
    {
        return ($this->workflow_status ?? self::STATUS_PENDING_ASSIGNMENT) === self::STATUS_PENDING_ASSIGNMENT;
    }

    public function isAssignedToAuditor(): bool
    {
        return $this->workflow_status === self::STATUS_ASSIGNED_TO_AUDITOR;
    }

    public function isAuditCompleted(): bool
    {
        return $this->workflow_status === self::STATUS_AUDIT_COMPLETED;
    }

    public function isCapaSubmitted(): bool
    {
        return $this->workflow_status === self::STATUS_CAPA_SUBMITTED;
    }

    public function isAuditClosed(): bool
    {
        return $this->workflow_status === self::STATUS_AUDIT_CLOSED;
    }

    public function needsCapa(): bool
    {
        if ($this->overall_score < 0.85) {
            return true;
        }

        foreach ($this->dimensions as $dim) {
            if ($dim->score_percentage < 0.85 || in_array($dim->status, ['YELLOW', 'ORANGE', 'RED'])) {
                return true;
            }
        }

        return false;
    }

    public function scopeFiltered($query, ?array $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['execution_status'])) {
            if ($filters['execution_status'] === 'ASSIGNED') {
                $query->whereIn('workflow_status', [self::STATUS_ASSIGNED_TO_AUDITOR, self::STATUS_PENDING_ASSIGNMENT]);
            } elseif ($filters['execution_status'] === 'DONE') {
                $query->whereIn('workflow_status', [self::STATUS_AUDIT_COMPLETED, self::STATUS_CAPA_SUBMITTED, self::STATUS_AUDIT_CLOSED]);
            }
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
