<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">
                {{ $type === 'entries' ? 'Macchine in entrata' : 'Macchine in uscita' }}
            </div>
            <div class="pm-page-subtitle">
                {{ $date->format('d/m/Y') }}
            </div>
        </div>
    </x-slot>

    <div style="display:flex;gap:8px;margin-bottom:24px" class="pm-animate">
        <a href="{{ route('calendar', ['parking_id' => $parkingId]) }}" class="pm-btn pm-btn-secondary">
            Vista calendario
        </a>
        <a href="{{ route('calendar.day', ['type' => 'entries', 'parking_id' => $parkingId, 'date' => $date->toDateString()]) }}" class="pm-btn {{ $type === 'entries' ? 'pm-btn-primary' : 'pm-btn-secondary' }}">
            Entrate
        </a>
        <a href="{{ route('calendar.day', ['type' => 'exits', 'parking_id' => $parkingId, 'date' => $date->toDateString()]) }}" class="pm-btn {{ $type === 'exits' ? 'pm-btn-primary' : 'pm-btn-secondary' }}">
            Uscite
        </a>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--pm-border); padding-bottom:16px; margin-bottom:24px;" class="pm-animate">
        <div style="display:flex; align-items:baseline; gap:12px;">
            <div style="font-size:24px; font-weight:600; color:var(--pm-text); font-family:var(--pm-mono); line-height:1">
                {{ $date->format('d / m') }}
            </div>
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <a href="{{ route('calendar.day', ['type' => $type, 'parking_id' => $parkingId, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="pm-btn pm-btn-secondary pm-btn-sm">◄ Giorno prima</a>
            <a href="{{ route('calendar.day', ['type' => $type, 'parking_id' => $parkingId, 'date' => now()->toDateString()]) }}" class="pm-btn pm-btn-secondary pm-btn-sm">Oggi</a>
            <a href="{{ route('calendar.day', ['type' => $type, 'parking_id' => $parkingId, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="pm-btn pm-btn-secondary pm-btn-sm">Giorno dopo ►</a>
            <a href="{{ route('calendar.day.export', ['type' => $type, 'parking_id' => $parkingId, 'date' => $date->toDateString()]) }}" class="pm-btn pm-btn-secondary pm-btn-sm" style="margin-left: 16px;">Esporta Excel</a>
        </div>
    </div>

    <div class="pm-animate-2">
        @if($reservations->isEmpty())
            <div class="pm-card" style="padding: 32px; text-align: center; color: var(--pm-text-muted);">
                Nessuna macchina in {{ $type === 'entries' ? 'entrata' : 'uscita' }} per questa data.
            </div>
        @else
            <div class="pm-card">
                <div class="pm-table-wrapper">
                    <table class="pm-table">
                    <thead>
                        <tr>
                            <th>Ora</th>
                            <th>Targa</th>
                            <th>Volo</th>
                            <th>Cliente</th>
                            <th>Telefono</th>
                            <th>Prodotto</th>
                            <th>Posti</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reservations as $reservation)
                            <tr>
                                <td class="pm-mono">
                                    {{ $type === 'entries' ? $reservation->starts_at->format('H:i') : $reservation->ends_at->format('H:i') }}
                                </td>
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
                                <td class="pm-mono">
                                    {{ $reservation->spots }}
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="check_{{ $reservation->id }}" {{ ($type === 'entries' ? $reservation->has_entered : $reservation->has_exited) ? 'checked' : '' }} onchange="toggleMovement({{ $reservation->id }}, '{{ $type === 'entries' ? 'entered' : 'exited' }}', this.checked)" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid var(--pm-border); cursor: pointer;">
                                        <label for="check_{{ $reservation->id }}" style="font-size: 13px; color: var(--pm-text-muted); cursor: pointer; user-select: none; margin: 0;">
                                            {{ $reservation->parkingProduct->name ?? 'Veicolo' }} {{ $type === 'entries' ? 'entrato' : 'uscito' }}
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif
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
