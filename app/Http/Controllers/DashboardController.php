<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\Platform;
use App\Enums\ReservationStatus;
use App\Services\AlertService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $parkings = Parking::active()->get();

        if ($parkings->isEmpty()) {
            abort(404, 'Nessun parcheggio attivo configurato nel sistema.');
        }

        $parkingIds = $parkings->pluck('id');

        $physicalTotal = $parkings->sum(function($p) {
            return $p->getComputedTotalSpots();
        });

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $weekEnd = Carbon::today()->addDays(7);
        $monthEnd = Carbon::today()->endOfMonth();

        // 1. Occupazione Fisica (FOTOGRAFIA DI OGGI sull'intero parco)
        $physicalOccupied = Reservation::whereIn('parking_id', $parkingIds)
            ->active()
            ->overlapping($today, $tomorrow)
            ->sum('spots');

        $allocatedSpots = \App\Models\ParkingCapacityAllocation::whereIn('parking_id', $parkingIds)
            ->active()
            ->overlapping($today, $tomorrow)
            ->sum('spots');

        $totalOccupied = $physicalOccupied + $allocatedSpots;
        $physicalPct = $physicalTotal > 0 ? round(($totalOccupied / $physicalTotal) * 100) : 0;

        // 2. Performance Commerciale per Canale (OGGI)
        $commercialPerformance = Platform::query()
            ->with([
                'listings.reservations' => function ($query) use ($today, $tomorrow, $parkingIds) {
                    $query->whereIn('parking_id', $parkingIds)->active()->overlapping($today, $tomorrow);
                },
                'listings'
            ])
            ->active()
            ->get()
            ->map(function ($platform) use ($physicalTotal) {
                // Posti generati da questa piattaforma OGGI
                $soldToday = $platform->listings->flatMap->reservations->sum('spots');

                return [
                    'platform' => $platform->name,
                    'sold_today' => $soldToday,
                    'impact_pct' => $physicalTotal > 0 ? round(($soldToday / $physicalTotal) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('sold_today')
            ->values();

        // 3. Prenotazioni attive OGGI (Dettaglio lista, limitate alle prime 8)
        $todayReservations = Reservation::query()
            ->whereIn('parking_id', $parkingIds)
            ->with(['parkingListing.platform'])
            ->active()
            ->overlapping($today, $tomorrow)
            ->orderByDesc('starts_at')
            ->take(8)
            ->get();

        // Contatori generali
        $stats = [
            'today_count' => Reservation::whereIn('parking_id', $parkingIds)->active()->overlapping($today, $tomorrow)->count(),
            'week_count' => Reservation::whereIn('parking_id', $parkingIds)->active()->overlapping($today, $weekEnd)->count(),
            'month_count' => Reservation::whereIn('parking_id', $parkingIds)->active()->overlapping($today, $monthEnd)->count(),
            'total_active' => Reservation::whereIn('parking_id', $parkingIds)->active()->count(),
            'cancelled_month' => Reservation::whereIn('parking_id', $parkingIds)->where('status', ReservationStatus::Cancelled->value)
                ->whereMonth('created_at', $today->month)
                ->count(),
        ];

        // Alerts (Futura & Analitica) - aggregati su tutti i parcheggi attivi
        $alerts = (new AlertService())->getAlertsForParkings($parkings);

        return view('dashboard', compact(
            'parkings',
            'physicalTotal',
            'physicalOccupied',
            'allocatedSpots',
            'physicalPct',
            'commercialPerformance',
            'todayReservations',
            'stats',
            'alerts'
        ));
    }

    public function calendar(Request $request)
    {
        $parkings = Parking::active()->orderBy('id')->get();
        if ($parkings->isEmpty()) {
            abort(404, 'Nessun parcheggio attivo configurato nel sistema.');
        }

        $parkingId = $request->input('parking_id');
        $parking = $parkingId ? $parkings->firstWhere('id', $parkingId) : $parkings->first();
        
        if (!$parking) {
            $parking = $parkings->first();
        }

        $totalSpots = $parking->getComputedTotalSpots();
        $platforms = \App\Models\Platform::active()->get();
        $products = \App\Models\ParkingProduct::where('parking_id', $parking->id)->active()->orderBy('sort_order')->get();
        
        return view('calendar', compact('parking', 'parkings', 'platforms', 'products', 'totalSpots'));
    }

    public function calendarData(Request $request)
    {
        $parkings = Parking::active()->orderBy('id')->get();
        if ($parkings->isEmpty()) {
            return response()->json(['reservations' => [], 'from' => null, 'to' => null, 'days' => 0]);
        }

        $parkingId = $request->input('parking_id');
        $parking = $parkingId ? $parkings->firstWhere('id', $parkingId) : $parkings->first();
        
        if (!$parking) {
            $parking = $parkings->first();
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $to = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $reservations = Reservation::query()
            ->where('parking_id', $parking->id)
            ->with(['parkingListing.platform', 'parkingProduct'])
            ->active()
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('starts_at', [$from, $to])
                    ->orWhereBetween('ends_at', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->where('starts_at', '<=', $from)
                            ->where('ends_at', '>=', $to);
                    });
            })
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'customer_name' => $r->customer_name,
                'license_plate' => $r->license_plate,
                'platform' => $r->parkingListing?->platform?->name ?? 'Unknown',
                'platform_slug' => $r->parkingListing?->platform?->slug ?? 'unknown',
                'product_name' => $r->parkingProduct?->name ?? 'Senza Categoria',
                'product_code' => $r->parkingProduct?->code ?? 'unknown',
                'starts_at' => $r->starts_at->format('Y-m-d'),
                'ends_at' => $r->ends_at->format('Y-m-d'),
                'starts_at_time' => $r->starts_at->format('H:i'),
                'ends_at_time' => $r->ends_at->format('H:i'),
                'spots' => $r->spots,
                'status' => $r->status->value,
                'price' => $r->price,
            ]);

        return response()->json([
            'reservations' => $reservations,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'days' => $from->daysInMonth,
        ]);
    }
}
