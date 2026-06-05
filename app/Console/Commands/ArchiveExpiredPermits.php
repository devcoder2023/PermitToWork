<?php

namespace App\Console\Commands;

use App\Enums\PermitStatus;
use App\Models\WorkPermit;
use Illuminate\Console\Command;

class ArchiveExpiredPermits extends Command
{
    protected $signature = 'permits:archive-expired';

    protected $description = 'Archive EXPIRED permits at the end of the day';

    public function handle(): int
    {
        $permits = WorkPermit::query()
            ->where('status', PermitStatus::Expired->value)
            ->get();

        $count = 0;
        foreach ($permits as $permit) {
            $permit->update(['status' => PermitStatus::Archived]);
            $count++;
        }

        $this->info("Archived {$count} expired permits.");

        return self::SUCCESS;
    }
}
