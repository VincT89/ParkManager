<?php

namespace App\Enums;

enum InventoryType: string
{
  case Shared = 'shared';
  case Isolated = 'isolated';
  case Unknown = 'unknown';

  public function label(): string
  {
    return match ($this) {
      InventoryType::Shared => 'Condiviso',
      InventoryType::Isolated => 'Dedicato',
      InventoryType::Unknown => 'Non definito',
    };
  }
}
