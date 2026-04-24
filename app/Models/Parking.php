<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parking extends Model 
{
  protected $fillable = [
    'name',
    'address',
    'total_spots',
    'capacity_mode',
    'notes',
    'is_active',
  ];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'total_spots' => 'integer',
    ];
  }

  public function listings(): HasMany
  {
      return $this->hasMany(ParkingListing::class);
  }

  public function blocks(): HasMany
  {
      return $this->hasMany(AvailabilityBlock::class);
  }

  public function products()
  {
      return $this->hasMany(ParkingProduct::class);
  }

  public function allocations()
  {
      return $this->hasMany(ParkingCapacityAllocation::class);
  }

  public function getComputedTotalSpots(): int
  {
      return (int) $this->products()->where('is_active', true)->sum('capacity');
  }

  public function scopeActive($query)
  {
      return $query->where('is_active', true);
  }
}