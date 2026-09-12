<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inventory:release-expired')]
#[Description('Release expired inventory reservations')]
class ReleaseExpiredReservations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ReservationService $reservationService): int
    {
        $released = $reservationService->releaseExpired();

        $this->info("Released {$released} expired reservation(s).");

        return self::SUCCESS;
    }
}
