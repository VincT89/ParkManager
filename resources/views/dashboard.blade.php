<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">{{ $parkings->count() > 1 ? 'Dashboard Globale' : ($parkings->first()?->name ?? 'Parcheggio') }}</div>
            <div class="pm-page-subtitle">{{ now()->isoFormat('dddd D MMMM YYYY') }}</div>
        </div>
        <a href="{{ route('reservations.create') }}" class="pm-btn pm-btn-primary">
            Nuova prenotazione
        </a>
    </x-slot>

    <x-flash-message />



    {{-- Stats Globali Centralizzate --}}
    <div class="pm-stats-grid pm-mb-16">
        <div class="pm-stat pm-animate-1">
            <div class="pm-stat-label">Occupazione oggi</div>
            <div class="pm-stat-value blue">{{ $physicalOccupied + $allocatedSpots }} / {{ $physicalTotal }}</div>
            <div class="pm-stat-delta">{{ $physicalPct }}% della capienza totale (include riservati)</div>
        </div>
        <div class="pm-stat pm-animate-2">
            <div class="pm-stat-label">Attive (settimana)</div>
            <div class="pm-stat-value green">{{ $stats['week_count'] }}</div>
            <div class="pm-stat-delta">prenotazioni future attive</div>
        </div>
        <div class="pm-stat pm-animate-3">
            <div class="pm-stat-label">Questo mese</div>
            <div class="pm-stat-value amber">{{ $stats['month_count'] }}</div>
            <div class="pm-stat-delta">{{ now()->format('M Y') }}</div>
        </div>
        <div class="pm-stat pm-animate-4">
            <div class="pm-stat-label">Cancellate (mese)</div>
            <div class="pm-stat-value red">{{ $stats['cancelled_month'] }}</div>
            <div class="pm-stat-delta">volume di no-show/disdette</div>
        </div>
    </div>

    {{-- Nessun alert legacy di canale fisso qui, the AlertService handles dangers based on central capacity directly
    into the topmost alerts block --}}

    <div class="pm-grid-2">

        <div class="pm-gap" style="height: 100%;">
            {{-- Prenotazioni oggi --}}
            <div class="pm-card pm-animate-3" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                <div class="pm-card-header">
                    <div class="pm-card-title">Prenotazioni attive</div>
                    <div class="pm-card-badge">{{ now()->format('d M') }}</div>
                </div>
                @if ($todayReservations->isEmpty())
                    <p class="pm-text-muted" style="font-size:13px">Nessuna prenotazione attiva oggi.</p>
                @else
                    <style>
                        .pm-table-scrollable th {
                            position: sticky;
                            top: 0;
                            background-color: white;
                            z-index: 10;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        }
                    </style>
                    <div class="pm-table-wrapper pm-table-scrollable" style="flex: 1; overflow-y: auto;">
                        <table class="pm-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Canale</th>
                                    <th>Arrivo</th>
                                    <th>Partenza</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todayReservations as $reservation)
                                    <tr>
                                        <td>
                                            <div class="pm-td-main">{{ $reservation->customer_name }}</div>
                                            @if ($reservation->customer_email)
                                                <div class="pm-td-sub">{{ $reservation->customer_email }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="pm-platform">
                                                <div class="pm-dot blue"></div>
                                                {{ $reservation->parkingListing->platform->name }}
                                            </div>
                                        </td>
                                        <td class="pm-mono">{{ $reservation->starts_at->format('d-m H:i') }}</td>
                                        <td class="pm-mono">{{ $reservation->ends_at->format('d-m H:i') }}</td>
                                        <td>
                                            @php
                                                $statusColor = match ($reservation->status) {
                                                    \App\Enums\ReservationStatus::Confirmed => 'green',
                                                    \App\Enums\ReservationStatus::Cancelled => 'red',
                                                    \App\Enums\ReservationStatus::Pending => 'amber',
                                                    default => 'gray',
                                                };
                                            @endphp
                                            <span class="pm-badge {{ $statusColor }}">
                                                {{ $reservation->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if ($stats['today_count'] > 8)
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 4px 0 0 0; border-bottom: none;">
                                        <a href="{{ route('reservations.index', ['date_from' => now()->format('Y-m-d'), 'date_to' => now()->format('Y-m-d')]) }}" class="pm-text-primary" style="text-decoration: none; font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: var(--pm-text-muted);">
                                            Vedi tutte le {{ $stats['today_count'] }} prenotazioni &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                @endif
            </div>

        </div>

        {{-- Sidebar --}}
        <div class="pm-gap">
            <div class="pm-card pm-animate-2">
                <div class="pm-card-header">
                    <div class="pm-card-title">Azioni rapide</div>
                </div>
                <div class="pm-quick-actions">
                    <a href="{{ route('reservations.create') }}" class="pm-quick-action">
                        <div class="pm-quick-action-left">
                            <div class="pm-quick-action-icon blue">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <line x1="7" y1="1" x2="7" y2="13" />
                                    <line x1="1" y1="7" x2="13" y2="7" />
                                </svg>
                            </div>
                            Nuova prenotazione
                        </div>
                        <span class="pm-quick-action-arrow">›</span>
                    </a>
                    <a href="{{ route('availability-blocks.create') }}" class="pm-quick-action">
                        <div class="pm-quick-action-left">
                            <div class="pm-quick-action-icon amber">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="1" y="1" width="12" height="12" rx="2" />
                                    <line x1="7" y1="4" x2="7" y2="10" />
                                </svg>
                            </div>
                            Aggiungi blocco
                        </div>
                        <span class="pm-quick-action-arrow">›</span>
                    </a>
                    <a href="{{ route('reservations.index') }}" class="pm-quick-action">
                        <div class="pm-quick-action-left">
                            <div class="pm-quick-action-icon green">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <rect x="1" y="3" width="12" height="9" rx="1" />
                                    <line x1="1" y1="6" x2="13" y2="6" />
                                </svg>
                            </div>
                            Tutte le prenotazioni
                        </div>
                        <span class="pm-quick-action-arrow">›</span>
                    </a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('platforms.index') }}" class="pm-quick-action">
                            <div class="pm-quick-action-left">
                                <div class="pm-quick-action-icon blue">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="7" cy="7" r="5" />
                                        <line x1="7" y1="4" x2="7" y2="7" />
                                        <line x1="7" y1="7" x2="9" y2="9" />
                                    </svg>
                                </div>
                                Gestione piattaforme
                            </div>
                            <span class="pm-quick-action-arrow">›</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Performance Commerciale Canali (Spostato a destra, sostituisce Canali Attivi) --}}
            <div class="pm-card pm-animate-3">
                <div class="pm-card-header">
                    <div class="pm-card-title">Impatto commerciale canali</div>
                    <div class="pm-card-badge">oggi</div>
                </div>
                @forelse ($commercialPerformance as $channel)
                    <div class="pm-channel-row" style="{{ !$loop->last ? 'margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid var(--pm-border-color);' : '' }}">
                        <div class="pm-channel-meta" style="margin-bottom: 6px;">
                            <div class="pm-channel-name">{{ $channel['platform'] }}</div>
                            <div class="pm-channel-nums" style="font-size: 11px;">
                                {{ $channel['sold_today'] }} posti ({{ $channel['impact_pct'] }}%)
                            </div>
                        </div>
                        <div class="pm-progress-track">
                            <div class="pm-progress-fill green"
                                style="width: {{ $channel['sold_today'] > 0 ? min(100, $channel['impact_pct']) : 0 }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="pm-text-muted" style="font-size:13px">Nessun canale configurato.</p>
                @endforelse
            </div>
        </div>

    </div>

</x-app-layout>