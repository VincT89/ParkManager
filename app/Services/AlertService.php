<?php

namespace App\Services;

use App\Models\Platform;
use App\Models\Reservation;
use App\Models\Parking;
use App\Enums\ReservationStatus;
use Carbon\Carbon;

class AlertService
{
    // Soglie configurabili
    const OCCUPANCY_WARNING_PCT = 80;
    const OCCUPANCY_DANGER_PCT = 95;
    const DAYS_AHEAD_CHECK = 7;
    const CANCELLATION_THRESHOLD = 3;

    public function getAlertsForParkings(\Illuminate\Support\Collection $parkings): array
    {
        $allAlerts = [];
        foreach ($parkings as $parking) {
            $allAlerts = array_merge($allAlerts, $this->getAlerts($parking));
        }

        // De-duplicate alerts if needed, or group them.
        // For cancellation alerts, they are per platform, so we might get duplicates if multiple parkings trigger it.
        // The makeAlert uses md5(level+topic+message) as ID. If the exact same alert is generated,
        // we can filter unique by ID.
        $uniqueAlerts = [];
        foreach ($allAlerts as $alert) {
            $uniqueAlerts[$alert['id']] = $alert;
        }

        // Re-sort
        $alerts = array_values($uniqueAlerts);
        usort($alerts, fn($a, $b) => $a['level'] === 'danger' ? -1 : 1);

        return $alerts;
    }

    public function getAlerts(Parking $parking): array
    {
        $alerts = [];
        $totalSpots = $parking->getComputedTotalSpots();
        if ($totalSpots <= 0) {
            $alerts[] = $this->makeAlert(
                'danger',
                'Configurazione Base',
                'Nessun prodotto attivo o capienza nulla configurata nel parcheggio.',
                'Il sistema operativo è paralizzato. Aggiungi e configura almeno un ParkingProduct (Categoria) all\'interno del parcheggio.',
                route('dashboard'),
                'Configura Prodotti'
            );
            return $alerts; // Ferma ogni altra diagnostica se manca il setup di base
        }

        // Check di configurazione / doppia verità
        if ($totalSpots > $parking->total_spots) {
            $alerts[] = $this->makeAlert(
                'danger',
                'Configurazione',
                "La somma delle capacità dei prodotti ({$totalSpots}) supera la capienza fisica configurata del parcheggio ({$parking->total_spots}).",
                'Il sistema sta operando su una capacità falsa. Modifica le configurazioni dei prodotti o aggiorna il parcheggio.',
                route('dashboard'), // In assenza di ProductController, si rimanda a dashboard o un url futuro
                'Risolvi'
            );
        } elseif ($totalSpots < $parking->total_spots && $totalSpots > 0) {
            $alerts[] = $this->makeAlert(
                'warning',
                'Configurazione',
                "La somma delle capacità dei prodotti ({$totalSpots}) è inferiore alla capienza fisica configurata del parcheggio ({$parking->total_spots}).",
                'C\'è della capacità fisica inutilizzata. Verifica se ci sono nuove categorie da creare o aggiornare le quantità.',
                route('dashboard'),
                'Verifica Prodotti'
            );
        }

        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // 1. Controllo OCCUPAZIONE FISICA (Oggi sull'intero Parcheggio)
        $occupiedToday = Reservation::where('parking_id', $parking->id)
            ->active()
            ->overlapping($today, $tomorrow)
            ->sum('spots');

        $pct = round(($occupiedToday / $totalSpots) * 100);

        if ($pct >= self::OCCUPANCY_DANGER_PCT) {
            $alerts[] = $this->makeAlert(
                'danger',
                'Fisica (Oggi)',
                "Occupazione critica — {$occupiedToday}/{$totalSpots} posti occupati ({$pct}%)",
                'Rischio overbooking immediato nel parcheggio.',
                route('reservations.index', [
                    'date_from' => $today->format('Y-m-d'),
                    'date_to' => $today->format('Y-m-d'),
                ]),
                'Verifica Prenotazioni'
            );
        } elseif ($pct >= self::OCCUPANCY_WARNING_PCT) {
            $alerts[] = $this->makeAlert(
                'warning',
                'Fisica (Oggi)',
                "Alta occupazione — {$occupiedToday}/{$totalSpots} posti ({$pct}%)",
                'Pochi posti rimasti. Tieni d\'occhio le entrate.',
                route('calendar'),
                'Vedi Calendario'
            );
        }

        // 2. Controllo OCCUPAZIONE FUTURA (prossimi 7 giorni sull'intero Parcheggio)
        for ($i = 1; $i <= self::DAYS_AHEAD_CHECK; $i++) {
            $day = Carbon::today()->addDays($i);
            $dayEnd = $day->copy()->addDay();

            $occupiedFut = Reservation::where('parking_id', $parking->id)
                ->active()
                ->overlapping($day, $dayEnd)
                ->sum('spots');

            if ($occupiedFut >= $totalSpots) {
                $alerts[] = $this->makeAlert(
                    'danger',
                    'Futura (' . $day->isoFormat('D MMMM') . ')',
                    "Tutto esaurito il {$day->isoFormat('D MMMM')} — {$occupiedFut}/{$totalSpots} posti",
                    'La capienza totale raggiungerà il limite. Valuta accorgimenti sul piazzale.',
                    route('calendar'),
                    'Visualizza Calendario'
                );
                break; // Mostra solo il primo giorno sold out
            }
        }

        // 3. Controllo PRESTAZIONI COMMERCIALI / ANOMALIE
        $platforms = Platform::with(['listings'])->active()->get();
        foreach ($platforms as $platform) {
            $listingIds = $platform->listings->pluck('id');
            if ($listingIds->isEmpty())
                continue;

            $cancelledToday = Reservation::whereIn('parking_listing_id', $listingIds)
                ->where('status', ReservationStatus::Cancelled->value ?? 'cancelled')
                ->whereDate('updated_at', Carbon::today())
                ->count();

            if ($cancelledToday >= self::CANCELLATION_THRESHOLD) {
                $alerts[] = $this->makeAlert(
                    'warning',
                    "Commerciale ({$platform->name})",
                    "{$cancelledToday} cancellazioni ricevute oggi su {$platform->name}",
                    'Numero insolito di cancellazioni nelle ultime ore da questo canale specifico.',
                    route('reservations.index', ['platform_id' => $platform->id, 'status' => 'cancelled']),
                    'Esamina Cancellazioni'
                );
            }
        }

        // Ordina: danger prima, poi warning
        usort($alerts, fn($a, $b) => $a['level'] === 'danger' ? -1 : 1);

        $dismissed = session('dismissed_alerts', []);
        $alerts = array_filter($alerts, function($a) use ($dismissed) {
            return !in_array($a['id'], $dismissed);
        });

        return array_values($alerts);
    }

    private function makeAlert(
        string $level,
        string $topic,
        string $message,
        string $suggestion,
        string $link,
        string $action_text
    ): array {
        $id = md5($level . $topic . $message);
        return [
            'id' => $id,
            'level' => $level, 
            'platform' => $topic, 
            'message' => $message, 
            'suggestion' => $suggestion, 
            'link' => $link, 
            'action_text' => $action_text
        ];
    }
}