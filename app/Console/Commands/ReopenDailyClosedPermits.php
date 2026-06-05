<?php

namespace App\Console\Commands;

use App\Enums\PermitStatus;
use App\Models\WorkPermit;
use Illuminate\Console\Command;

class ReopenDailyClosedPermits extends Command
{
    protected $signature = 'permits:reopen-daily-closed';

    protected $description = 'Reopen DAILY_CLOSED weekly permits at the start of the next working day';

    public function handle(): int
    {
        $today = today();

        $permits = WorkPermit::query()
            ->where('status', PermitStatus::DailyClosed->value)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereHas('permitType', function ($query): void {
                $query->where('duration_type', 'weekly');
            })
            ->get();

        $count = 0;
        foreach ($permits as $permit) {
            $permit->update(['status' => PermitStatus::Active]);
            $count++;
        }

        $this->info("Reopened {$count} daily-closed weekly permits.");

        return self::SUCCESS;
    }
}
