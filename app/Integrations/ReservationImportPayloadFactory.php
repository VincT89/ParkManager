<?php

namespace App\Integrations;

use App\Integrations\DTO\NormalizedReservation;
use App\Models\ParkingProduct;

class ReservationImportPayloadFactory
{
    /**
     * Converts a NormalizedReservation and its resolved ParkingProduct
     * into the array payload expected by ReservationService::importFromExternal().
     */
    public function makePayload(NormalizedReservation $dto, ParkingProduct $product): array
    {
        return [
            'external_id'        => $dto->external_id,
            'parking_product_id' => $product->id,
            'customer_name'      => $dto->customer_name,
            'customer_email'     => $dto->customer_email,
            'customer_phone'     => $dto->customer_phone,
            'license_plate'      => $dto->license_plate,
            'starts_at'          => $dto->starts_at->format('Y-m-d H:i:s'),
            'ends_at'            => $dto->ends_at->format('Y-m-d H:i:s'),
            'spots'              => $dto->spots,
            'price'              => $dto->price,
            'notes'              => $dto->notes,
            'status'             => $dto->status ?? 'confirmed',
            'flight_reference'   => $dto->flight_reference,
            'raw_data'           => $dto->raw_data,
        ];
    }

    /**
     * Converts a NormalizedReservation into a payload for cancellation.
     * Does not require a resolved ParkingProduct.
     */
    public function makeCancellationPayload(NormalizedReservation $dto): array
    {
        return [
            'external_id' => $dto->external_id,
            'status'      => 'cancelled',
            'raw_data'    => $dto->raw_data,
        ];
    }
}
