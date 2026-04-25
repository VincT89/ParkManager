<?php

namespace App\Services;

use App\Models\Reservation;

class PricingService
{
    public function amountForReservation(Reservation $reservation): int
    {
        // returns cents
        return (int) round($reservation->price * 100);
    }

    public function decimalAmountForReservation(Reservation $reservation): string
    {
        return number_format($reservation->price, 2, '.', '');
    }
}
