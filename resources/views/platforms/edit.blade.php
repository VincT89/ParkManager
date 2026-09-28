<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Modifica piattaforma</div>
            <div class="pm-page-subtitle">{{ $platform->name }}</div>
        </div>
        <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">
            Torna alle piattaforme
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('platforms.update', $platform) }}" class="pm-form">
            @csrf
            @parkingContext
            @method('PUT')

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="platform-name">Nome della piattaforma</label>
                    <input type="text" id="platform-name" name="name"
                           value="{{ old('name', $platform->name) }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="platform-slug">Codice piattaforma</label>
                    <input type="text" id="platform-slug" name="slug" list="platform-codes" aria-describedby="platform-code-help" @readonly($platform->uses_legacy_credentials && !auth()->user()->can('manage-shared-connections'))
                           value="{{ old('slug', $platform->slug) }}"
                           required class="pm-input" />
                </div>
                <datalist id="platform-codes"><option value="parkos">Parkos</option><option value="parking-my-car">ParkingMyCar</option><option value="vologio">Vologio</option><option value="website">Sito web</option></datalist>
                <p id="platform-code-help" class="pm-form-note" style="grid-column:1 / -1">Il codice collega la piattaforma alla sua integrazione. Seleziona un valore dall’elenco per usare uno dei collegamenti disponibili.</p>
                <div class="pm-form-group">
                    <label class="pm-label" for="platform-website">Sito web</label>
                    <input type="url" id="platform-website" name="website"
                           value="{{ old('website', $platform->website) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="platform-contact_email">Email di contatto</label>
                    <input type="email" id="platform-contact_email" name="contact_email"
                           value="{{ old('contact_email', $platform->contact_email) }}"
                           class="pm-input" />
                </div>
            </div>

            <div class="pm-checkbox-group">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', $platform->is_active) ? 'checked' : '' }}
                       class="pm-checkbox" />
                <label for="is_active" class="pm-checkbox-label">Piattaforma attiva</label>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Salva modifiche</button>
                <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

    @if(in_array($platform->slug, ['parkos', 'parking-my-car', 'vologio']))
        <div class="pm-card" style="max-width:720px;padding:20px;margin-top:24px">
            <h2 class="pm-card-title">Connessione alla piattaforma</h2>
            @if($platform->uses_legacy_credentials)
                <p style="margin-top:12px">Questa connessione utilizza le credenziali già configurate. Per modificarle rivolgiti al responsabile della connessione.</p>
            @else
                <p style="margin:12px 0">{{ $platform->connection_settings ? 'Connessione configurata. Lascia vuoti i campi che vuoi mantenere.' : 'Configura l’account della piattaforma per questa gestione.' }}</p>
                <form method="POST" action="{{ route('platforms.connection', $platform) }}" class="pm-form">@csrf @parkingContext @method('PUT')
                    @foreach($platform->slug === 'vologio' ? ['api_key' => 'Chiave API', 'client_id' => 'Client ID'] : ['client_id' => 'Client ID', 'client_secret' => 'Client secret', 'username' => 'Nome utente', 'password' => 'Password'] as $credential => $label)
                        <div class="pm-form-group"><label class="pm-label" for="credential-{{ $credential }}">{{ $label }}</label><input type="password" class="pm-input" id="credential-{{ $credential }}" name="credentials[{{ $credential }}]" autocomplete="new-password" maxlength="2000">@error('credentials.'.$credential)<div role="alert">{{ $message }}</div>@enderror</div>
                    @endforeach
                    <button type="submit" class="pm-btn pm-btn-primary">Salva connessione</button>
                </form>
            @endif
        </div>
    @endif
    @if(!$platform->uses_legacy_credentials && in_array($platform->slug, ['parkos', 'parking-my-car', 'vologio']))
        @can('manage-shared-connections')
            <div class="pm-card" style="max-width:720px;padding:20px;margin-top:24px">
                <form method="POST" action="{{ route('platforms.connection', $platform) }}" onsubmit="return confirm('Utilizzare l’account della piattaforma già configurato? Verifica che includa il nuovo parcheggio.');">@csrf @parkingContext @method('PUT')
                    <input type="hidden" name="use_shared_connection" value="1">
                    <p style="margin-bottom:12px">Puoi utilizzare l’account esistente se comprende anche questo parcheggio.</p><button type="submit" class="pm-btn pm-btn-secondary">Utilizza la connessione esistente</button>
                </form>
            </div>
        @endcan
    @endif
    @foreach($platform->listings as $linkedListing)
        <div class="pm-card" style="max-width:720px;padding:20px;margin-top:24px">
            <form method="POST" action="{{ route('platforms.listing.update', [$platform, $linkedListing]) }}" class="pm-form">@csrf @parkingContext @method('PUT')
                <label class="pm-label" for="external-{{ $linkedListing->id }}">Codice del parcheggio su {{ $platform->name }}</label>
                <p id="external-id-help" class="pm-form-note">Inserisci il codice assegnato dalla piattaforma a questo parcheggio.</p>
                <input class="pm-input" aria-describedby="external-id-help" id="external-{{ $linkedListing->id }}" name="external_id" value="{{ $linkedListing->external_id }}" maxlength="255" @readonly($platform->uses_legacy_credentials && !auth()->user()->can('manage-shared-connections'))>
                <label><input type="checkbox" name="is_active" value="1" @checked($linkedListing->is_active)> Collegamento attivo</label>
                @if(!$platform->uses_legacy_credentials || auth()->user()->can('manage-shared-connections'))<button type="submit" class="pm-btn pm-btn-secondary">Salva collegamento</button>@endif
            </form>
        </div>
    @endforeach
    {{-- Sezione collegamento parcheggio --}}
    <div class="pm-card pm-animate" style="max-width:720px;margin-top:24px">
        <div style="font-size:16px;font-weight:600;margin-bottom:16px;border-bottom:1px solid var(--pm-border);padding-bottom:12px;color:var(--pm-text);">
            Collegamento al parcheggio
        </div>
        @if($platform->listings->count() > 0)
            <div style="margin-bottom: 24px;">
                <div style="font-size: 14px; font-weight: 500; margin-bottom: 8px; color: var(--pm-text-muted);">Parcheggi attualmente collegati:</div>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($platform->listings as $listing)
                        <span class="pm-badge {{ $listing->is_active ? 'green' : 'gray' }}">{{ $listing->parking->name }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($parkings->count() > 0)
            <form method="POST" action="{{ route('platforms.attach', $platform) }}" class="pm-form">
                @csrf
            @parkingContext
                
                <div class="pm-form-grid-2" style="align-items:end;">
                    <div class="pm-form-group" style="margin:0;">
                        <label class="pm-label pm-label-required" for="connection-parking_id">Parcheggio da collegare</label>
                        <select id="connection-parking_id" name="parking_id" required class="pm-select">
                            <option value="">Seleziona parcheggio...</option>
                            @foreach ($parkings as $parking)
                                <option value="{{ $parking->id }}">
                                    {{ $parking->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin:0;">
                        <button type="submit" class="pm-btn pm-btn-primary">Collega parcheggio</button>
                    </div>
                </div>
            </form>
        @elseif($platform->listings->isEmpty())
            <div style="padding: 16px; background: #f8fafc; border: 1px solid var(--pm-border); border-radius: 8px; text-align: center; color: var(--pm-text-muted); font-size: 14px;">
                Il parcheggio selezionato non è attivo. Attivalo dalla pagina Parcheggi per collegarlo a questa piattaforma.
            </div>
        @endif
    </div>

    {{-- Sezione Mapping Prodotti (solo manuale per ora) --}}
    <div class="pm-card pm-animate" style="max-width:720px;margin-top:24px">
        <div style="font-size:16px;font-weight:600;margin-bottom:16px;border-bottom:1px solid var(--pm-border);padding-bottom:12px;color:var(--pm-text);">
            Associa le categorie
        </div>
        
        <div class="mb-6">
            @if($mappings->count() > 0)
                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codice sulla piattaforma</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome sulla piattaforma</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria del parcheggio</th>
                                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($mappings as $mapping)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $mapping->external_ref }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $mapping->external_name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $mapping->parkingProduct->name ?? 'Categoria rimossa' }}
                                        <div class="text-xs text-gray-400">{{ $mapping->parkingProduct->parking->name ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        <form method="POST" action="{{ route('platforms.mappings.destroy', $mapping) }}" onsubmit="return confirm('Rimuovere questa associazione?');">
                                            @csrf
            @parkingContext
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 mb-4">Nessuna associazione presente. Indica a quale categoria del parcheggio corrisponde ogni codice ricevuto dalla piattaforma.</p>
            @endif
        </div>

        <div style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--pm-text);">
            Nuova associazione
        </div>
        <form method="POST" action="{{ route('platforms.mappings.store', $platform) }}" class="pm-form">
            @csrf
            @parkingContext
            
            <div class="pm-form-grid-2" style="align-items:end;">
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label pm-label-required" for="connection-external_ref">Codice della categoria sulla piattaforma</label>
                    <input type="text" id="connection-external_ref" name="external_ref" required class="pm-input" placeholder="es. STANDARD_1" />
                    @error('external_ref')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label" for="connection-external_name">Nome sulla piattaforma (facoltativo)</label>
                    <input type="text" id="connection-external_name" name="external_name" class="pm-input" placeholder="es. Parcheggio Standard" />
                </div>
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label pm-label-required" for="connection-parking_product_id">Categoria del parcheggio</label>
                    <select id="connection-parking_product_id" name="parking_product_id" required class="pm-select">
                        <option value="">Seleziona una categoria...</option>
                        @foreach ($platform->listings as $listing)
                            @if($listing->parking)
                                <optgroup label="{{ $listing->parking->name }}">
                                    @foreach ($listing->parking->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} (Posti: {{ $product->capacity }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div style="margin:0;">
                    <button type="submit" class="pm-btn pm-btn-primary">Salva associazione</button>
                </div>
            </div>
        </form>
    </div>

</x-app-layout>
