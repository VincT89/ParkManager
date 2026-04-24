<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Modified = 'modified';
    case NoShow = 'no_show';

    public function label(): string
    {
      return match($this) {
        ReservationStatus::Pending => 'In attesa',
        ReservationStatus::Confirmed => 'Confermata',
        ReservationStatus::Cancelled => 'Annullata',
        ReservationStatus::Modified => 'Modificata',
        ReservationStatus::NoShow => 'Non presentato',
      };
    }

    public function isActive(): bool
    {
      return in_array($this, [
        ReservationStatus::Pending,
        ReservationStatus::Confirmed,
        ReservationStatus::Modified,
      ]);
    }

}
