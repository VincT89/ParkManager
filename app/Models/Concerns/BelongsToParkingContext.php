<?php

namespace App\Models\Concerns;

use App\Models\Parking;
use App\Models\ParkingListing;
use App\Models\ParkingProduct;
use App\Models\Payment;
use App\Models\Platform;
use App\Models\PlatformProductMapping;
use App\Models\Reservation;
use App\Models\SyncLog;
use App\Services\ActivityRecorder;
use App\Tenancy\ParkingContext;
use App\Tenancy\ParkingScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToParkingContext
{
    protected static function bootBelongsToParkingContext(): void
    {
        static::addGlobalScope(new ParkingScope);
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function (Model $model) use ($event) {
                if (! $model instanceof SyncLog) {
                    app(ActivityRecorder::class)->record($event, $model, $model->getChanges());
                }
            });
        }
        static::saving(function (Model $model) {
            $context = app(ParkingContext::class);
            if (! $context->restricted) {
                return;
            }
            if ($model->exists && ! $model->newQuery()->whereKey($model->getKey())->exists()) {
                abort(404);
            }
            if ($model instanceof Parking || $model instanceof Platform) {
                $model->management_id ??= $context->management?->id;
                abort_unless($model->management_id && (int) $model->management_id === $context->management?->id, 404);
            } elseif ($model instanceof Payment) {
                Reservation::findOrFail($model->reservation_id);
            } elseif ($model instanceof PlatformProductMapping) {
                ParkingProduct::findOrFail($model->parking_product_id);
                Platform::findOrFail($model->platform_id);
            } elseif ($model instanceof SyncLog) {
                ParkingListing::findOrFail($model->parking_listing_id);
                Platform::findOrFail($model->platform_id);
            } else {
                $model->parking_id ??= $context->parking?->id;
                abort_unless($model->parking_id && (int) $model->parking_id === $context->parking?->id, 404);
            }
        });
    }
}
