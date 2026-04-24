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
                                    <a href="{{ route('reservations.edit', $reservation) }}"
                                        class="pm-btn pm-btn-secondary pm-btn-sm">
                                        Modifica
                                    </a>
                                    @if ($reservation->status !== \App\Enums\ReservationStatus::Cancelled)
                                        <form method="POST" action="{{ route('reservations.destroy', $reservation) }}"
                                            onsubmit="return confirm('Confermi la cancellazione?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">
                                                Cancella
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