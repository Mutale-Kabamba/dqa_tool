<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class RoleContextBannerWidget extends Widget
{
    protected static string $view = 'filament.widgets.role-context-banner';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();

        $isMeal = $user?->isMealOfficer() ?? false;
        $isPO = $user?->isProjectOfficer() ?? false;
        $isAuditor = $user?->isAuditor() ?? false;

        $managedProjects = $user ? $user->managedProjects()->pluck('name')->toArray() : [];
        $projectsLabel = !empty($managedProjects) ? implode(', ', $managedProjects) : 'Assigned Project Portfolio';

        return [
            'user' => $user,
            'isMeal' => $isMeal,
            'isPO' => $isPO,
            'isAuditor' => $isAuditor,
            'isDualRole' => ($isPO && $isAuditor && !$isMeal),
            'projectsLabel' => $projectsLabel,
        ];
    }
}
