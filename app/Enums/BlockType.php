<?php

namespace App\Enums;

enum BlockType: string
{
  case Maintenance = 'maintenance';
  case Closure = 'closure';
  case Internal = 'internal';
  case Other = 'other';

  public function label(): string
  {
    return match ($this) {
      BlockType::Maintenance => 'Manutenzione',
      BlockType::Closure => 'Chiusura',
      BlockType::Internal => 'Interno',
      BlockType::Other => 'Altro',
    };
  }
}
