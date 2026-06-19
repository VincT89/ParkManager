<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Piattaforme</div>
            <div class="pm-page-subtitle">gestione canali di vendita e posti assegnati</div>
        </div>

        <div class="pm-platform-actions">
            <button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('historical-sync-modal').classList.add('pm-modal-open')">
                Recupera storico
            </button>

            <form method="POST" action="{{ route('platforms.future-sync') }}" onsubmit="return confirm('Recuperare le prenotazioni da oggi ai prossimi 6 mesi?')">
                @csrf
                <button type="submit" class="pm-btn pm-btn-secondary">
                    Prossimi 6 mesi
                </button>
            </form>

            <form method="POST" action="{{ route('platforms.sync') }}" onsubmit="return confirm('Vuoi avviare il sync manuale?')">
                @csrf
                <button type="submit" class="pm-btn pm-btn-primary">
                    Sincronizza Piattaforme
                </button>
            </form>

            <a href="{{ route('platforms.create') }}" class="pm-btn pm-btn-primary">
                Nuova piattaforma
            </a>
        </div>
    </x-slot>

    <div id="historical-sync-modal" class="pm-modal">
        <div class="pm-modal-card">
            <div class="pm-card-header">
                <div class="pm-card-title">Recupera storico</div>
                <button type="button" onclick="document.getElementById('historical-sync-modal').classList.remove('pm-modal-open')" class="pm-modal-close">&times;</button>
            </div>
            <div class="pm-card-body">
                <p class="text-sm text-gray-500 mb-4">
                    Recupera le prenotazioni con entrata, uscita o permanenza nel periodo selezionato.
                </p>
                <form method="POST" action="{{ route('platforms.historical-sync') }}" class="pm-form">
                    @csrf
                    <div class="pm-field" style="margin-bottom:12px;">
                        <label class="pm-label">Data inizio</label>
                        <input type="date" name="from" class="pm-input" required>
                    </div>
                    <div class="pm-field" style="margin-bottom:16px;">
                        <label class="pm-label">Data fine</label>
                        <input type="date" name="to" class="pm-input" required>
                    </div>
                    <div class="pm-modal-actions">
                        <button type="button" class="pm-btn" onclick="document.getElementById('historical-sync-modal').classList.remove('pm-modal-open')">Annulla</button>
                        <button type="submit" class="pm-btn pm-btn-primary">Conferma recupero</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-flash-message />

    @if (!empty($lastSyncLog))
        <div class="pm-card pm-sync-summary">
            <div class="pm-card-header">
                <div>
                    <div class="pm-card-title">Ultima sincronizzazione</div>
                    <div class="pm-text-muted" style="font-size:12px">
                        {{ $lastSyncLog->created_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}
                        · stato: {{ $lastSyncLog->status }}
                        · origine: {{ $lastSyncLog->source }}
                    </div>
                </div>
            </div>

            <div class="pm-sync-stats">
                <div>Create: {{ $lastSyncLog->reservations_created }}</div>
                <div>Aggiornate: {{ $lastSyncLog->reservations_updated }}</div>
                <div>Saltate: {{ $lastSyncLog->reservations_skipped }}</div>
                <div>Errori: {{ $lastSyncLog->reservations_failed }}</div>
            </div>
        </div>
    @endif

    <div class="pm-gap">
        @forelse ($platforms as $platform)
            <div class="pm-card pm-animate">

                {{-- Header piattaforma --}}
                <div class="pm-platform-header">
                    <div class="pm-platform-info">
                        <div class="pm-platform-details">
                            <div class="pm-platform-title-row">
                                <span class="pm-platform-name">
                                    {{ $platform->name }}
                                </span>
                                <span class="pm-badge {{ $platform->is_active ? 'green' : 'red' }}">
                                    {{ $platform->is_active ? 'Attiva' : 'Inattiva' }}
                                </span>
                            </div>
                            <div class="pm-platform-meta">
                                <span class="pm-text-muted pm-text-mono">{{ $platform->slug }}</span>
                                @if ($platform->website)
                                    <a href="{{ $platform->website }}" target="_blank" class="pm-platform-link">
                                        {{ $platform->website }}
                                    </a>
                                @endif
                                @if ($platform->contact_email)
                                    <span class="pm-text-muted" style="font-size:12px">
                                        {{ $platform->contact_email }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pm-platform-actions-sm">
                        <a href="{{ route('platforms.edit', $platform) }}"
                           class="pm-btn pm-btn-secondary pm-btn-sm">
                            Modifica
                        </a>
                        <form method="POST"
                              action="{{ route('platforms.destroy', $platform) }}"
                              onsubmit="return confirm('Disattivare la piattaforma?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">
                                Disattiva
                            </button>
                        </form>
                    </div>
                </div>

                <div style="border-top:1px solid var(--pm-border);padding-top:16px;margin-top:16px;">
                    <div style="font-size:12px;font-weight:500;color:var(--pm-text-muted);
                                text-transform:uppercase;letter-spacing:0.08em;
                                font-family:var(--pm-mono);margin-bottom:12px">
                        Parcheggi Connessi
                    </div>
                    @if ($platform->listings->isNotEmpty())
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach ($platform->listings as $listing)
                                <span class="pm-badge" style="background:rgba(255,255,255,0.05);color:var(--pm-text);border:1px solid var(--pm-border);">
                                    {{ $listing->parking->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div style="font-size:13px;color:var(--pm-text-muted)">
                            Nessun parcheggio associato a questa piattaforma. Entra in Modifica per collegarne uno.
                        </div>
                    @endif
                </div>

            </div>
        @empty
            <div class="pm-card pm-animate">
                <p class="pm-text-muted" style="font-size:13px;text-align:center;padding:16px 0">
                    Nessuna piattaforma configurata.
                </p>
            </div>
        @endforelse
    </div>

</x-app-layout>