<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Piattaforme</div>
            <div class="pm-page-subtitle">gestione canali di vendita e Posti assegnati</div>
        </div>
        <a href="{{ route('platforms.create') }}" class="pm-btn pm-btn-primary">
            Nuova piattaforma
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-gap">
        @forelse ($platforms as $platform)
            <div class="pm-card pm-animate">

                {{-- Header piattaforma --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div style="display:flex;flex-direction:column;gap:2px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="font-size:15px;font-weight:600;color:var(--pm-text)">
                                    {{ $platform->name }}
                                </span>
                                <span class="pm-badge {{ $platform->is_active ? 'green' : 'red' }}">
                                    {{ $platform->is_active ? 'Attiva' : 'Inattiva' }}
                                </span>
                            </div>
                            <div style="display:flex;align-items:center;gap:16px">
                                <span class="pm-text-muted pm-text-mono">{{ $platform->slug }}</span>
                                @if ($platform->website)
                                    <a href="{{ $platform->website }}" target="_blank"
                                       style="font-size:12px;color:var(--pm-accent);text-decoration:none">
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
                    <div style="display:flex;gap:8px">
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