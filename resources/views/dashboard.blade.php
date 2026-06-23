<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">{{ $parkings->count() > 1 ? 'Dashboard' : ($parkings->first()?->name ?? 'Parcheggio') }}</div>
            <div class="pm-page-subtitle">{{ now()->isoFormat('dddd D MMMM') }}</div>
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

        <div class="pm-stat pm-animate-2" x-data="{ mode: 'incoming' }">
            <div class="pm-kpi-switch-header">
                <div class="pm-stat-label" x-text="mode === 'incoming' ? 'Prenotazioni in entrata' : 'Prenotazioni in uscita'"></div>

                <div class="pm-segmented-control">
                    <button
                        type="button"
                        class="pm-segmented-btn"
                        :class="{ 'active': mode === 'incoming' }"
                        @click="mode = 'incoming'"
                    >
                        Entrata
                    </button>

                    <button
                        type="button"
                        class="pm-segmented-btn"
                        :class="{ 'active amber': mode === 'outgoing' }"
                        @click="mode = 'outgoing'"
                    >
                        Uscita
                    </button>
                </div>
            </div>

            <div class="pm-kpi-switch-value">
                <div class="pm-stat-value green" x-show="mode === 'incoming'">
                    {{ $incomingReservationsToday }}
                </div>

                <div class="pm-stat-value amber" x-show="mode === 'outgoing'" x-cloak>
                    {{ $outgoingReservationsToday }}
                </div>
            </div>

            <div class="pm-stat-delta" x-text="mode === 'incoming' ? 'arrivi previsti oggi' : 'uscite previste oggi'"></div>
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

    <div class="pm-grid-2">

        <div class="pm-gap">

             {{-- Movimenti di oggi --}}
            <div class="pm-card pm-animate-4" style="margin-bottom: 24px;" x-data="{ mode: 'incoming' }">
                <div class="pm-card-header">
                    <div class="pm-card-title">Movimenti di oggi</div>
                    <div class="pm-segmented-control">
                        <button
                            type="button"
                            class="pm-segmented-btn"
                            :class="{ 'active': mode === 'incoming' }"
                            @click="mode = 'incoming'"
                        >
                            Entrata
                        </button>
        
                        <button
                            type="button"
                            class="pm-segmented-btn"
                            :class="{ 'active amber': mode === 'outgoing' }"
                            @click="mode = 'outgoing'"
                        >
                            Uscita
                        </button>
                    </div>
                </div>
        
                <style>
                    .pm-table-scrollable-movements th {
                        position: sticky;
                        top: 0;
                        background-color: white;
                        z-index: 10;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                    }
                </style>
        
                {{-- Entrate --}}
                <div x-show="mode === 'incoming'">
                    @if($dashboardIncomingReservationsToday->isEmpty())
                        <div style="padding: 32px; text-align: center; color: var(--pm-text-muted); font-size: 13px;">
                            Nessuna macchina in entrata oggi.
                        </div>
                    @else
                        <div class="pm-table-wrapper pm-table-scrollable-movements" style="max-height: 400px; overflow-y: auto;">
                            <table class="pm-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th>Ora</th>
                                        <th>Targa</th>
                                        <th>Volo</th>
                                        <th>Cliente</th>
                                        <th>Telefono</th>
                                        <th>Prodotto</th>
                                        <th>Posti</th>
                                        <th title="Clienti Navetta">Pax</th>
                                        <th>Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboardIncomingReservationsToday as $reservation)
                                        <tr>
                                            <td class="pm-mono">{{ $reservation->starts_at->format('H:i') }}</td>
                                            <td>
                                                @if($reservation->license_plate)
                                                    <span style="font-family: var(--pm-mono); font-weight: 600; color: var(--pm-accent); background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2);">
                                                        {{ $reservation->license_plate }}
                                                    </span>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($reservation->flight_reference)
                                                    <a href="https://www.flightradar24.com/data/flights/{{ strtolower($reservation->flight_reference) }}" target="_blank" style="font-family: var(--pm-mono); font-weight: 600; color: var(--pm-accent); text-decoration: none; background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2);">
                                                        {{ $reservation->flight_reference }}
                                                    </a>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="pm-td-main">{{ $reservation->customer_name }}</div>
                                            </td>
                                            <td>
                                                @if($reservation->customer_phone)
                                                    <a href="tel:{{ $reservation->customer_phone }}" style="color: var(--pm-accent); text-decoration: none; font-weight: 500;">
                                                        {{ $reservation->customer_phone }}
                                                    </a>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="pm-td-main">{{ $reservation->parkingProduct->name ?? 'N/D' }}</div>
                                                <div class="pm-td-sub">{{ $reservation->parking->name ?? 'N/D' }}</div>
                                            </td>
                                            <td class="pm-mono">{{ $reservation->spots }}</td>
                                            <td class="pm-mono">{{ $reservation->passengers_count ?? 1 }}</td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <input type="checkbox" id="check_in_{{ $reservation->id }}" {{ $reservation->has_entered ? 'checked' : '' }} onchange="toggleMovement({{ $reservation->id }}, 'entered', this.checked)" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--pm-border); cursor: pointer;">
                                                    <label for="check_in_{{ $reservation->id }}" style="font-size: 13px; color: var(--pm-text-muted); cursor: pointer; user-select: none; margin: 0;">
                                                        {{ $reservation->parkingProduct->name ?? 'Veicolo' }} entrato
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
        
                {{-- Uscite --}}
                <div x-show="mode === 'outgoing'" x-cloak>
                    @if($dashboardOutgoingReservationsToday->isEmpty())
                        <div style="padding: 32px; text-align: center; color: var(--pm-text-muted); font-size: 13px;">
                            Nessuna macchina in uscita oggi.
                        </div>
                    @else
                        <div class="pm-table-wrapper pm-table-scrollable-movements" style="max-height: 400px; overflow-y: auto;">
                            <table class="pm-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th>Ora</th>
                                        <th>Targa</th>
                                        <th>Volo</th>
                                        <th>Cliente</th>
                                        <th>Telefono</th>
                                        <th>Prodotto</th>
                                        <th>Posti</th>
                                        <th title="Clienti Navetta">Pax</th>
                                        <th>Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboardOutgoingReservationsToday as $reservation)
                                        <tr>
                                            <td class="pm-mono">{{ $reservation->ends_at->format('H:i') }}</td>
                                            <td>
                                                @if($reservation->license_plate)
                                                    <span style="font-family: var(--pm-mono); font-weight: 600; color: var(--pm-accent); background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2);">
                                                        {{ $reservation->license_plate }}
                                                    </span>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($reservation->flight_reference)
                                                    <a href="https://www.flightradar24.com/data/flights/{{ strtolower($reservation->flight_reference) }}" target="_blank" style="font-family: var(--pm-mono); font-weight: 600; color: var(--pm-accent); text-decoration: none; background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2);">
                                                        {{ $reservation->flight_reference }}
                                                    </a>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="pm-td-main">{{ $reservation->customer_name }}</div>
                                            </td>
                                            <td>
                                                @if($reservation->customer_phone)
                                                    <a href="tel:{{ $reservation->customer_phone }}" style="color: var(--pm-accent); text-decoration: none; font-weight: 500;">
                                                        {{ $reservation->customer_phone }}
                                                    </a>
                                                @else
                                                    <span class="pm-text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="pm-td-main">{{ $reservation->parkingProduct->name ?? 'N/D' }}</div>
                                                <div class="pm-td-sub">{{ $reservation->parking->name ?? 'N/D' }}</div>
                                            </td>
                                            <td class="pm-mono">{{ $reservation->spots }}</td>
                                            <td class="pm-mono">{{ $reservation->passengers_count ?? 1 }}</td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <input type="checkbox" id="check_out_{{ $reservation->id }}" {{ $reservation->has_exited ? 'checked' : '' }} onchange="toggleMovement({{ $reservation->id }}, 'exited', this.checked)" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--pm-border); cursor: pointer;">
                                                    <label for="check_out_{{ $reservation->id }}" style="font-size: 13px; color: var(--pm-text-muted); cursor: pointer; user-select: none; margin: 0;">
                                                        {{ $reservation->parkingProduct->name ?? 'Veicolo' }} uscito
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            {{-- Prenotazioni oggi --}}
            <div class="pm-card pm-animate-3" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div class="pm-card-header">
                    <div class="pm-card-title">Ultime prenotazioni</div>
                    <div class="pm-card-badge">ultime 5</div>
                </div>
                @if ($latestReservations->isEmpty())
                    <p class="pm-text-muted" style="font-size:13px">Nessuna prenotazione trovata.</p>
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
                                @foreach ($latestReservations as $reservation)
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
                                                {{ $reservation->parkingListing?->platform?->name ?? '-' }}
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
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 4px 0 0 0; border-bottom: none;">
                                        <a href="{{ route('reservations.index') }}" class="pm-text-primary" style="text-decoration: none; font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; color: var(--pm-text-muted);">
                                            Vedi tutte le prenotazioni &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </tfoot>
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

    <script>
        function toggleMovement(reservationId, type, isChecked) {
            fetch(`/reservations/${reservationId}/toggle-movement`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    type: type,
                    value: isChecked
                })
            }).then(res => {
                if (!res.ok) {
                    alert('Errore nel salvataggio dello stato.');
                }
            }).catch(err => {
                console.error(err);
                alert('Errore di rete nel salvataggio.');
            });
        }
    </script>
</x-app-layout>
