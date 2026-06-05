<?php

namespace App\Enums;

enum PermitStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Active = 'active';
    case DailyClosed = 'daily_closed';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Expired = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::UnderReview => 'Under Review',
            self::Rejected => 'Rejected',
            self::Approved => 'Approved',
            self::Active => 'Active',
            self::DailyClosed => 'Daily Closed',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
            self::Expired => 'Expired',
            self::Archived => 'Archived',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
