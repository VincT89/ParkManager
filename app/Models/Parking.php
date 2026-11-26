<?php

namespace App\Models;

use App\Enums\CarWashType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parking extends Model 
{
  use \App\Models\Concerns\BelongsToParkingContext;

  protected static function booted(): void
  {
      static::creating(fn (self $parking) => $parking->public_booking_key ??= (string) \Illuminate\Support\Str::uuid());
  }

  public function management()
  {
      return $this->belongsTo(Management::class);
  }

  protected $fillable = [
    'name',
    'address',
    'total_spots',
    'capacity_mode',
    'notes',
    'is_active',
    'car_wash_interior_price',
    'car_wash_exterior_price',
    'car_wash_both_price',
  ];

  protected $attributes = [
      'car_wash_interior_price' => '10.00',
      'car_wash_exterior_price' => '10.00',
      'car_wash_both_price' => '20.00',
  ];

  protected $hidden = ['whatsapp_review_settings'];

  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'total_spots' => 'integer',
      'car_wash_interior_price' => 'decimal:2',
      'car_wash_exterior_price' => 'decimal:2',
      'car_wash_both_price' => 'decimal:2',
      'whatsapp_reviews_enabled_at' => 'integer',
      'whatsapp_review_settings' => 'encrypted:array',
    ];
  }

  public function carWashPrice(CarWashType $type): float
  {
      return (float) $this->getAttribute($type->priceAttribute());
  }

  public function whatsappReviewsEnabled(): bool
  {
      return $this->is_active && $this->whatsapp_reviews_enabled_at !== null;
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
