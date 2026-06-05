<?php

namespace App\Enums;

enum Role: string
{
    case SystemAdmin = 'system_admin';
    case ExecutionEngineer = 'execution_engineer';
    case SiteManager = 'site_manager';
    case PermitOfficer = 'permit_officer';
    case WorkSupervisor = 'work_supervisor';
    case HseOfficer = 'hse_officer';
    case Consultant = 'consultant';
    case QaInspector = 'qa_inspector';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Admin',
            self::ExecutionEngineer => 'Execution Engineer',
            self::SiteManager => 'Site Manager',
            self::PermitOfficer => 'Permit Officer',
            self::WorkSupervisor => 'Work Supervisor',
            self::HseOfficer => 'HSE Officer',
            self::Consultant => 'Consultant',
            self::QaInspector => 'QA Inspector',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
