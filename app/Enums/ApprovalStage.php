<?php

namespace App\Enums;

enum ApprovalStage: string
{
    case SiteManager = 'site_manager';
    case PermitOfficer = 'permit_officer';
    case WorkSupervisor = 'work_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::SiteManager => 'Site Manager',
            self::PermitOfficer => 'Permit Officer',
            self::WorkSupervisor => 'Work Supervisor',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
