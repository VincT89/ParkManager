<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Analisi redditività</div>
            <div class="pm-page-subtitle">{{ $monthLabel }}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <a href="{{ $prevMonthUrl }}" class="pm-btn pm-btn-secondary pm-btn-sm" title="Mese precedente">&#8249; Mese prec</a>
            <a href="{{ $nextMonthUrl }}" class="pm-btn pm-btn-secondary pm-btn-sm" title="Mese successivo">Mese succ &#8250;</a>
        </div>
    </x-slot>

    <x-flash-message />

    {{-- Totali --}}
    <div class="pm-stats-grid pm-mb-16 pm-animate">
        <div class="pm-stat">
            <div class="pm-stat-label">Entrate mese</div>
            <div class="pm-stat-value green">€ {{ number_format($totals['revenue'], 2) }}</div>
            <div class="pm-stat-delta">tutte le piattaforme</div>
        </div>
        <div class="pm-stat">
            <div class="pm-stat-label">Prenotazioni</div>
            <div class="pm-stat-value blue">{{ $totals['count'] }}</div>
            <div class="pm-stat-delta">questo mese</div>
        </div>
        <div class="pm-stat">
            <div class="pm-stat-label">Prezzo medio</div>
            <div class="pm-stat-value amber">€ {{ number_format($totals['avg_price'], 2) }}</div>
            <div class="pm-stat-delta">per prenotazione</div>
        </div>
        <div class="pm-stat">
            <div class="pm-stat-label">Cancellate</div>
            <div class="pm-stat-value red">{{ $totals['cancelled'] }}</div>
            <div class="pm-stat-delta">questo mese</div>
        </div>
    </div>

    {{-- Canali --}}
    <div class="pm-gap">
        @foreach ($channelStats as $stat)
            <div class="pm-card pm-animate-2">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr;gap:24px;align-items:start">

                    {{-- Nome canale --}}
                    <div>
                        <div style="font-size:15px;font-weight:600;color:var(--pm-text);margin-bottom:4px">
                            {{ $stat['platform'] }}
                        </div>
                        <div class="pm-text-muted pm-text-mono" style="font-size:12px">
                            Piattaforma connessa
                        </div>
                    </div>

                    {{-- Entrate --}}
                    <div>
                        <div class="pm-stat-label">Entrate</div>
                        <div
                            style="font-size:22px;font-weight:600;color:var(--pm-green);font-family:var(--pm-mono);letter-spacing:-0.02em">
                            € {{ number_format($stat['this_revenue'], 2) }}
                        </div>
                        @if ($stat['revenue_change'] !== null)
                            <div
                                style="font-size:12px;margin-top:4px;color:{{ $stat['revenue_change'] >= 0 ? 'var(--pm-green)' : 'var(--pm-red)' }}">
                                {{ $stat['revenue_change'] >= 0 ? '+' : '' }}{{ $stat['revenue_change'] }}%
                                <span class="pm-text-muted">vs mese scorso</span>
                            </div>
                        @endif
                    </div>

                    {{-- Prenotazioni --}}
                    <div>
                        <div class="pm-stat-label">Prenotazioni</div>
                        <div
                            style="font-size:22px;font-weight:600;color:var(--pm-accent);font-family:var(--pm-mono);letter-spacing:-0.02em">
                            {{ $stat['this_count'] }}
                        </div>
                        @if ($stat['count_change'] !== null)
                            <div
                                style="font-size:12px;margin-top:4px;color:{{ $stat['count_change'] >= 0 ? 'var(--pm-green)' : 'var(--pm-red)' }}">
                                {{ $stat['count_change'] >= 0 ? '+' : '' }}{{ $stat['count_change'] }}%
                                <span class="pm-text-muted">vs mese scorso</span>
                            </div>
                        @endif
                    </div>

                    {{-- Prezzo medio --}}
                    <div>
                        <div class="pm-stat-label">Prezzo medio</div>
                        <div
                            style="font-size:22px;font-weight:600;color:var(--pm-amber);font-family:var(--pm-mono);letter-spacing:-0.02em">
                            € {{ number_format($stat['avg_price'], 2) }}
                        </div>
                        <div style="font-size:12px;margin-top:4px" class="pm-text-muted">
                            {{ $stat['cancelled'] }} cancellate
                        </div>
                    </div>

                    {{-- Trend sparkline --}}
                    <div>
                        <div class="pm-stat-label" style="margin-bottom:8px">Trend 6 mesi</div>
                        <div style="display:flex;align-items:flex-end;gap:4px;height:40px">
                            @php
                                $maxRevenue = max(array_column($stat['trend'], 'revenue')) ?: 1;
                            @endphp
                            @foreach ($stat['trend'] as $i => $t)
                                @php
                                    $height = $maxRevenue > 0 ? max(4, round(($t['revenue'] / $maxRevenue) * 40)) : 4;
                                    $isLast = $i === count($stat['trend']) - 1;
                                @endphp
                                <div style="display:flex;flex-direction:column;align-items:center;gap:3px;flex:1">
                                    <div
                                        style="
                                        width:100%;
                                        height:{{ $height }}px;
                                        background:{{ $isLast ? 'var(--pm-accent)' : 'rgba(255,255,255,0.12)' }};
                                        border-radius:2px;
                                        transition:height 0.3s;
                                    ">
                                    </div>
                                    <div style="font-size:9px;font-family:var(--pm-mono);color:var(--pm-text-dim)">
                                        {{ $t['month'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            </div>
        @endforeach
    </div>

</x-app-layout>
