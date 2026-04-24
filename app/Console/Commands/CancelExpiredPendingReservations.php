<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cancel-expired-pending-reservations')]
#[Description('Command description')]
class CancelExpiredPendingReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:cancel-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Annulla le prenotazioni in stato pending che sono scadute.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expired = \App\Models\Reservation::where('status', \App\Enums\ReservationStatus::Pending->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $reservation) {
            $reservation->update([
                'status' => \App\Enums\ReservationStatus::Cancelled->value
            ]);
            $count++;
        }

        $this->info("Annullate {$count} prenotazioni pending scadute.");
    }
}
