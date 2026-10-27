<?php

namespace App\Console\Commands;

use App\Actions\Attendance\ManageAttendance;
use Illuminate\Console\Command;

final class FinalizeAttendance extends Command
{
    protected $signature = 'attendance:finalize';

    protected $description = 'Finalize expired QR windows and record absences idempotently.';

    public function handle(ManageAttendance $attendance): int
    {
        $count = $attendance->finalizeAllDue();
        $this->info('Finalized '.$count.' attendance windows.');

        return self::SUCCESS;
    }
}
