<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;

class AuditPolicy
{
    /**
     * Determine whether the user can view any audits.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the audit.
     */
    public function view(User $user, Audit $audit): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create audits.
     */
    public function create(User $user): bool
    {
        return $user->isMealOfficer();
    }

    /**
     * Determine whether the user can update the audit.
     */
    public function update(User $user, Audit $audit): bool
    {
        if ($user->isMealOfficer()) {
            return true;
        }

        $isOwnProject = ($audit->project_officer_id === $user->id || $audit->project?->project_officer_id === $user->id);

        // Anti-Self-Audit Policy: A Project Officer cannot be assigned to audit their own project or submissions
        if ($isOwnProject && $audit->auditor_id === $user->id) {
            return false;
        }

        // Project officer can update CAPA response tracking for their project
        if ($user->isProjectOfficer() && $isOwnProject) {
            return true;
        }

        // Auditor can update peer audits assigned to them
        if ($user->isAuditor()) {
            return $audit->auditor_id === $user->id || $audit->auditor_id === null;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the audit.
     * Only MEAL Officers can delete audits.
     */
    public function delete(User $user, Audit $audit): bool
    {
        return $user->isMealOfficer();
    }
}
