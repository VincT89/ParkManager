<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Monitoraggio Sistema</div>
            <div class="pm-page-subtitle">Rilevamento anomalie e gestione overbooking</div>
        </div>
    </x-slot>

    <x-flash-message />

    @if (count($alerts) === 0)
        <div class="pm-card pm-animate pm-alert-empty-state">
            <div class="pm-alert-empty-icon">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="pm-alert-message">
                    Nessuna emergenza attiva
                </div>
                <div class="pm-alert-suggestion">
                    Tutti i canali operano senza anomalie e con capacità sicura.
                </div>
            </div>
        </div>
    @else
        <div class="pm-gap">

            {{-- Contatori --}}
            <div class="pm-stats-grid pm-animate">
                <div class="pm-stat" style="border-top-color: var(--pm-accent)">
                    <div class="pm-stat-label">Alert in corso</div>
                    <div class="pm-stat-value amber">{{ count($alerts) }}</div>
                    <div class="pm-stat-delta">Segnalazioni complessive</div>
                </div>
                <div class="pm-stat" style="border-top-color: var(--pm-red)">
                    <div class="pm-stat-label">Stato Critico</div>
                    <div class="pm-stat-value red">
                        {{ count(array_filter($alerts, fn($a) => $a['level'] === 'danger')) }}
                    </div>
                    <div class="pm-stat-delta" style="color:var(--pm-red)">Richiede intervento</div>
                </div>
                <div class="pm-stat" style="border-top-color: var(--pm-amber)">
                    <div class="pm-stat-label">Attenzione</div>
                    <div class="pm-stat-value amber">
                        {{ count(array_filter($alerts, fn($a) => $a['level'] === 'warning')) }}
                    </div>
                    <div class="pm-stat-delta">Avvertimenti di capienza</div>
                </div>
                <div class="pm-stat" style="border-top-color: var(--pm-blue)">
                    <div class="pm-stat-label">Canali a rischio</div>
                    <div class="pm-stat-value blue">
                        {{ count(array_unique(array_column($alerts, 'platform'))) }}
                    </div>
                    <div class="pm-stat-delta">su {{ \App\Models\Platform::where('is_active', true)->count() }} piattaforme</div>
                </div>
            </div>

            {{-- Lista alert --}}
            <div class="pm-card pm-animate-2">
                <div class="pm-card-header" style="border-bottom: 1px solid var(--pm-border); padding-bottom: 16px; margin-bottom: 20px;">
                    <div class="pm-card-title">Interventi Richiesti</div>
                    <div class="pm-card-badge">Monitoraggio live: {{ now()->isoFormat('HH:mm') }}</div>
                </div>

                <div class="pm-gap">
                    @foreach ($alerts as $alert)
                        <div class="pm-alert-row {{ $alert['level'] }}">
                            <div class="pm-alert-icon-wrap {{ $alert['level'] }}">
                                @if($alert['level'] === 'danger')
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                @else
                                    <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>

                            <div style="flex:1">
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px">
                                    <span class="pm-alert-badge {{ $alert['level'] }}">
                                        {{ $alert['platform'] }}
                                    </span>
                                    <span class="pm-alert-context">
                                        • &nbsp; {{ $alert['level'] === 'danger' ? 'Azione Immediata' : 'Allerta Preventiva' }}
                                    </span>
                                </div>
                                <div class="pm-alert-message {{ $alert['level'] }}">
                                    {{ $alert['message'] }}
                                </div>
                                <div class="pm-alert-suggestion">
                                    <strong>Suggerimento:</strong> {{ $alert['suggestion'] }}
                                </div>
                            </div>

                            <div style="display:flex; align-items:center; align-self: center; margin-left:16px; gap:8px;">
                                <form action="{{ route('alerts.dismiss', $alert['id']) }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" class="pm-btn pm-btn-ghost" style="color: var(--pm-text-muted); font-size: 12px; padding: 6px 12px; height: auto;">
                                        Ignora
                                    </button>
                                </form>
                                <a href="{{ $alert['link'] }}" class="pm-btn pm-alert-action-btn {{ $alert['level'] }}">
                                    {{ $alert['action_text'] ?? 'Risolvi' }}
                                    <svg width="16" height="16" style="margin-left: 6px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Soglie attive --}}
            <div class="pm-card pm-animate-3">
                <div class="pm-card-header">
                    <div class="pm-card-title">Parametri di Allerta Globali</div>
                    <div class="pm-card-badge">Configurazione Attiva</div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px">
                    <div class="pm-param-card">
                        <div class="pm-stat-label">Allerta Capacità</div>
                        <div class="pm-param-value" style="color:var(--pm-amber)">
                            {{ \App\Services\AlertService::OCCUPANCY_WARNING_PCT }}%
                        </div>
                        <div class="pm-param-desc">
                            Scatta l'avviso preventivo per potenziale esaurimento posti.
                        </div>
                    </div>
                    <div class="pm-param-card">
                        <div class="pm-stat-label">Soglia Critica Estrema</div>
                        <div class="pm-param-value" style="color:var(--pm-red)">
                            {{ \App\Services\AlertService::OCCUPANCY_DANGER_PCT }}%
                        </div>
                        <div class="pm-param-desc">
                            Rischio altissimo di overbooking. Azione immediata consigliata.
                        </div>
                    </div>
                    <div class="pm-param-card">
                        <div class="pm-stat-label">Orizzonte di Previsione</div>
                        <div class="pm-param-value" style="color:var(--pm-accent)">
                            {{ \App\Services\AlertService::DAYS_AHEAD_CHECK }} Giorni
                        </div>
                        <div class="pm-param-desc">
                            L'algoritmo analizza prenotazioni fino a 7 giorni nel futuro.
                        </div>
                    </div>
                    <div class="pm-param-card">
                        <div class="pm-stat-label">Traffico Cancellazioni</div>
                        <div class="pm-param-value" style="color:var(--pm-amber)">
                            {{ \App\Services\AlertService::CANCELLATION_THRESHOLD }}+
                        </div>
                        <div class="pm-param-desc">
                            Soglia limite di cancellazioni giornaliere prima di un alert.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    @endif
</x-app-layout>