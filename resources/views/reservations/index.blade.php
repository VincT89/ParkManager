<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Prenotazioni</div>
            <div class="pm-page-subtitle">gestione prenotazioni multi-canale</div>
        </div>
        <a href="{{ route('reservations.create') }}" class="pm-btn pm-btn-primary">
            Nuova prenotazione
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-mb-16 pm-animate">
        <form method="GET" action="{{ route('reservations.index') }}" class="pm-filters">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cerca cliente (premi Invio)..." class="pm-input" onchange="this.form.submit()" />
            <select name="platform_id" class="pm-select" onchange="this.form.submit()">
                <option value="">Tutti i canali</option>
                @foreach ($platforms as $platform)
                    <option value="{{ $platform->id }}" {{ request('platform_id') == $platform->id ? 'selected' : '' }}>
                        {{ $platform->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="pm-select" onchange="this.form.submit()">
                <option value="">Tutti gli stati</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </select>
            <select name="sort_by" class="pm-select" onchange="this.form.submit()">
                <option value="created_at_desc" {{ request('sort_by', 'created_at_desc') == 'created_at_desc' ? 'selected' : '' }}>Data Inserimento (Più recenti)</option>
                <option value="starts_at_asc" {{ request('sort_by') == 'starts_at_asc' ? 'selected' : '' }}>Data Arrivo (Crescente)</option>
                <option value="starts_at_desc" {{ request('sort_by') == 'starts_at_desc' ? 'selected' : '' }}>Data Arrivo (Decrescente)</option>
                <option value="created_at_asc" {{ request('sort_by') == 'created_at_asc' ? 'selected' : '' }}>Data Inserimento (Meno recenti)</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="pm-input"
                onchange="this.form.submit()" />
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="pm-input"
                onchange="this.form.submit()" />
            <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary pm-btn-sm">Reset</a>
            <a href="{{ route('reservations.export', request()->query()) }}" class="pm-btn pm-btn-secondary pm-btn-sm">
                Esporta Excel
            </a>
        </form>
    </div>

    <div class="pm-card pm-animate-2">
        <div class="pm-table-wrapper">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Volo</th>
                        <th>Canale</th>
                        <th>Arrivo</th>
                        <th>Partenza</th>
                        <th>Posti</th>
                        <th>Prezzo</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td>
                                <div class="pm-td-main">{{ $reservation->customer_name }}</div>
                            </td>
                            <td>
                                @if ($reservation->flight_reference)
                                    <a href="https://www.flightradar24.com/data/flights/{{ strtolower($reservation->flight_reference) }}" target="_blank" style="font-family: var(--pm-mono); font-size: 11px; font-weight: 600; color: var(--pm-accent); text-decoration: none; background: rgba(249, 96, 32, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px solid rgba(249, 96, 32, 0.2); display: inline-block;">
                                        {{ $reservation->flight_reference }}
                                    </a>
                                @else
                                    <span class="pm-text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="pm-platform">
                                    <div class="pm-dot blue"></div>
                                    {{ $reservation->parkingListing->platform->name }}
                                </div>
                            </td>
                            <td class="pm-mono">{{ $reservation->starts_at->format('d-m-Y H:i') }}</td>
                            <td class="pm-mono">{{ $reservation->ends_at->format('d-m-Y H:i') }}</td>
                            <td class="pm-mono">{{ $reservation->spots }}</td>
                            <td class="pm-mono">
                                {{ $reservation->price ? '€ ' . number_format($reservation->price * $reservation->spots, 2) : '—' }}
                            </td>
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
                            <td>
                                <div style="display:flex;gap:8px;align-items:center">
                                    <a href="{{ route('reservations.show', $reservation) }}"
                                        class="pm-btn pm-btn-secondary pm-btn-sm" title="Vedi dettagli">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('reservations.edit', $reservation) }}"
                                        class="pm-btn pm-btn-secondary pm-btn-sm" title="Modifica">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    @if ($reservation->status !== \App\Enums\ReservationStatus::Cancelled)
                                        <form method="POST" action="{{ route('reservations.destroy', $reservation) }}"
                                            onsubmit="return confirm('Confermi la cancellazione?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm" title="Cancella">
                                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:32px 0;color:var(--pm-text-muted)">
                                Nessuna prenotazione trovata.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reservations->hasPages())
            <div class="pm-pagination">
                {{ $reservations->links('vendor.pagination.pm') }}
            </div>
        @endif
    </div>

</x-app-layout>