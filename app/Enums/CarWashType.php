<?php

namespace App\Enums;

enum CarWashType: string
{
    case Interior = 'interior';
    case Exterior = 'exterior';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Interior => 'Lavaggio interno',
            self::Exterior => 'Lavaggio esterno',
            self::Both => 'Lavaggio interno + esterno',
        };
    }

    public function priceAttribute(): string
    {
        return match ($this) {
            self::Interior => 'car_wash_interior_price',
            self::Exterior => 'car_wash_exterior_price',
            self::Both => 'car_wash_both_price',
        };
    }
}
