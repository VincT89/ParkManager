<x-app-layout>
    <x-slot name="header">
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%">
            <div>
                <div class="pm-page-title">Parcheggi</div>
                <div class="pm-page-subtitle">Gestione sedi e configurazioni globali</div>
            </div>
            <a href="{{ route('parkings.create') }}" class="pm-btn pm-btn-primary">
                Nuovo Parcheggio
            </a>
        </div>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate">
        <div class="pm-table-wrapper">
            <table class="pm-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Stato</th>
                        <th>Posti totali</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parkings as $parking)
                        <tr>
                            <td>
                                <div class="pm-td-main">{{ $parking->name }}</div>
                            </td>
                            <td>
                                @if ($parking->is_active)
                                    <span class="pm-badge green">Attivo</span>
                                @else
                                    <span class="pm-badge gray">Disattivato</span>
                                @endif
                            </td>
                            <td>
                                <span class="pm-mono">{{ $parking->total_spots }}</span>
                            </td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <a href="{{ route('parkings.edit', $parking) }}" class="pm-btn pm-btn-secondary pm-btn-sm">
                                        Modifica / Configura
                                    </a>
                                    
                                    @if ($parking->is_active)
                                        <form method="POST" action="{{ route('parkings.destroy', $parking) }}" onsubmit="return confirm('Sei sicuro di voler disattivare questo parcheggio?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm">
                                                Disattiva
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="pm-text-muted" style="text-align:center; padding:32px;">
                                Nessun parcheggio presente nel sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
