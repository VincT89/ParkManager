<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Dettaglio prenotazione</div>
            <div class="pm-page-subtitle">{{ $reservation->external_id }}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">
                Torna alla lista
            </a>
            <a href="{{ route('reservations.edit', $reservation) }}" class="pm-btn pm-btn-primary">
                Modifica
            </a>
        </div>
    </x-slot>

    <div class="pm-card pm-animate" style="max-width:800px; padding: 32px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 1px solid var(--pm-border-color);">
            <div>
                <h2 style="font-size: 20px; font-weight: 600; color: var(--pm-text); margin-bottom: 8px;">{{ $reservation->customer_name }}</h2>
                @if ($reservation->customer_email)
                    <div style="color: var(--pm-text-muted); font-size: 14px;">{{ $reservation->customer_email }}</div>
                @endif
                @if ($reservation->customer_phone)
                    <div style="color: var(--pm-text-muted); font-size: 14px;">{{ $reservation->customer_phone }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                @php
                    $statusColor = match ($reservation->status) {
                        \App\Enums\ReservationStatus::Confirmed => 'green',
                        \App\Enums\ReservationStatus::Cancelled => 'red',
                        \App\Enums\ReservationStatus::Pending => 'amber',
                        default => 'gray',
                    };
                @endphp
                <span class="pm-badge {{ $statusColor }}" style="font-size: 14px; padding: 6px 12px; margin-bottom: 12px; display: inline-block;">
                    {{ $reservation->status->label() }}
                </span>
                <div style="font-family: var(--pm-mono); font-size: 13px; color: var(--pm-text-muted);">
                    Inserita il {{ $reservation->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        <div class="pm-form-grid-2" style="margin-bottom: 32px;">
            <div>
                <div class="pm-label">Canale di vendita</div>
                <div style="font-weight: 500; font-size: 15px; color: var(--pm-text);">
                    <div class="pm-platform" style="display: inline-flex;">
                        <div class="pm-dot blue"></div>
                        {{ $reservation->parkingListing?->platform?->name ?? 'N/A' }}
                    </div>
                </div>
            </div>
            <div>
                <div class="pm-label">Categoria / Tipologia Posto</div>
                <div style="font-weight: 500; font-size: 15px; color: var(--pm-text);">
                    {{ $reservation->parkingProduct?->name ?? 'Nessuna categoria' }}
                </div>
            </div>
        </div>

        <div class="pm-form-grid-2" style="margin-bottom: 32px; background: var(--pm-bg-soft); padding: 16px; border-radius: 8px; border: 1px solid var(--pm-border-color);">
            <div>
                <div class="pm-label">Arrivo previsto</div>
                <div style="font-weight: 600; font-size: 16px; color: var(--pm-text);">
                    {{ $reservation->starts_at->format('d/m/Y') }} <span style="color: var(--pm-text-muted);">alle</span> {{ $reservation->starts_at->format('H:i') }}
                </div>
            </div>
            <div>
                <div class="pm-label">Partenza prevista</div>
                <div style="font-weight: 600; font-size: 16px; color: var(--pm-text);">
                    {{ $reservation->ends_at->format('d/m/Y') }} <span style="color: var(--pm-text-muted);">alle</span> {{ $reservation->ends_at->format('H:i') }}
                </div>
            </div>
        </div>

        <div class="pm-form-grid-3" style="margin-bottom: 32px;">
            <div>
                <div class="pm-label">Veicolo / Targa</div>
                @if($reservation->license_plate)
                    <div style="font-family: var(--pm-mono); font-weight: 600; font-size: 15px; color: var(--pm-text); background: var(--pm-bg-soft); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--pm-border-color); display: inline-block;">
                        {{ $reservation->license_plate }}
                    </div>
                @else
                    <span class="pm-text-muted">-</span>
                @endif
            </div>
            <div>
                <div class="pm-label">Riferimento Volo</div>
                @if($reservation->flight_reference)
                    <a href="https://www.flightradar24.com/data/flights/{{ strtolower($reservation->flight_reference) }}" target="_blank" style="font-family: var(--pm-mono); font-size: 15px; font-weight: 600; color: var(--pm-accent); text-decoration: none; background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2); display: inline-block;">
                        {{ $reservation->flight_reference }}
                    </a>
                @else
                    <span class="pm-text-muted">-</span>
                @endif
            </div>
            <div>
                <div class="pm-label">Posti occupati</div>
                <div style="font-weight: 600; font-size: 18px; color: var(--pm-text);">
                    {{ $reservation->spots }}
                </div>
            </div>
        </div>

        <div class="pm-form-grid-2" style="margin-bottom: 32px;">
            <div>
                <div class="pm-label">Prezzo Unitario</div>
                <div style="font-weight: 500; font-size: 15px; color: var(--pm-text);">
                    € {{ number_format($reservation->price, 2) }}
                </div>
            </div>
            <div>
                <div class="pm-label">Totale Pratica</div>
                <div style="font-weight: 700; font-size: 20px; color: var(--pm-green);">
                    € {{ number_format($reservation->price * $reservation->spots, 2) }}
                </div>
            </div>
        </div>

        @if ($reservation->notes)
            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--pm-border-color);">
                <div class="pm-label">Note Operative</div>
                <div style="background: #fffbea; border: 1px solid #fef08a; padding: 16px; border-radius: 8px; color: #854d0e; font-size: 14px; line-height: 1.5; white-space: pre-wrap;">{{ $reservation->notes }}</div>
            </div>
        @endif

    </div>

</x-app-layout>
