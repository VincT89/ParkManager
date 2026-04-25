<?php

namespace App\Http\Controllers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;

class PublicPaymentController extends Controller
{
    public function show(string $externalId)
    {
        $reservation = Reservation::where('external_id', $externalId)->firstOrFail();

        abort_if($reservation->status !== ReservationStatus::Pending, 404);
        abort_if($reservation->expires_at && $reservation->expires_at->isPast(), 410);

        return view('booking.payment', [
            'reservation' => $reservation,
            'stripePublicKey' => config('payments.stripe.public_key'),
            'paypalClientId' => config('payments.paypal.client_id'),
        ]);
    }
}
