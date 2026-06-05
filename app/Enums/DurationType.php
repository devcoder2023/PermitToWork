<?php

namespace App\Enums;

enum DurationType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
        };
    }
}
