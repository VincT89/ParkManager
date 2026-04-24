<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Modifica piattaforma</div>
            <div class="pm-page-subtitle">{{ $platform->name }}</div>
        </div>
        <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">
            Torna alla lista
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('platforms.update', $platform) }}" class="pm-form">
            @csrf
            @method('PUT')

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Nome</label>
                    <input type="text" name="name"
                           value="{{ old('name', $platform->name) }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Slug</label>
                    <input type="text" name="slug"
                           value="{{ old('slug', $platform->slug) }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Sito web</label>
                    <input type="url" name="website"
                           value="{{ old('website', $platform->website) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Email contatto</label>
                    <input type="email" name="contact_email"
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

    {{-- Sezione collegamento parcheggio --}}
    <div class="pm-card pm-animate" style="max-width:720px;margin-top:24px">
        <div style="font-size:16px;font-weight:600;margin-bottom:16px;border-bottom:1px solid var(--pm-border);padding-bottom:12px;color:var(--pm-text);">
            Collega Parcheggio
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
                
                <div class="pm-form-grid-2" style="align-items:end;">
                    <div class="pm-form-group" style="margin:0;">
                        <label class="pm-label pm-label-required">Collega Nuovo Parcheggio</label>
                        <select name="parking_id" required class="pm-select">
                            <option value="">Seleziona parcheggio...</option>
                            @foreach ($parkings as $parking)
                                <option value="{{ $parking->id }}">
                                    {{ $parking->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin:0;">
                        <button type="submit" class="pm-btn pm-btn-primary">Collega Canale</button>
                    </div>
                </div>
            </form>
        @else
            <div style="padding: 16px; background: #f8fafc; border: 1px solid var(--pm-border); border-radius: 8px; text-align: center; color: var(--pm-text-muted); font-size: 14px;">
                Tutti i parcheggi attivi sono già stati collegati a questa piattaforma.
            </div>
        @endif
    </div>

    {{-- Sezione Mapping Prodotti (solo manuale per ora) --}}
    <div class="pm-card pm-animate" style="max-width:720px;margin-top:24px">
        <div style="font-size:16px;font-weight:600;margin-bottom:16px;border-bottom:1px solid var(--pm-border);padding-bottom:12px;color:var(--pm-text);">
            Mapping Prodotti (Manuale)
        </div>
        
        <div class="mb-6">
            @if($mappings->count() > 0)
                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codice Esterno</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome (Opzionale)</th>
                                <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prodotto Interno</th>
                                <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Azioni</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($mappings as $mapping)
                                <tr>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $mapping->external_ref }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $mapping->external_name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $mapping->parkingProduct->name ?? 'Prodotto rimosso' }}
                                        <div class="text-xs text-gray-400">{{ $mapping->parkingProduct->parking->name ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-right">
                                        <form method="POST" action="{{ route('platforms.mappings.destroy', $mapping) }}" onsubmit="return confirm('Sicuro di voler eliminare questo mapping?');">
                                            @csrf
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
                <p class="text-sm text-gray-500 mb-4">Nessun mapping configurato. Associa i codici prodotto della piattaforma ai prodotti del parcheggio.</p>
            @endif
        </div>

        <div style="font-size:14px;font-weight:600;margin-bottom:12px;color:var(--pm-text);">
            Aggiungi nuovo mapping
        </div>
        <form method="POST" action="{{ route('platforms.mappings.store', $platform) }}" class="pm-form">
            @csrf
            
            <div class="pm-form-grid-2" style="align-items:end;">
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label pm-label-required">Codice Esterno (Ref)</label>
                    <input type="text" name="external_ref" required class="pm-input" placeholder="es. STANDARD_1" />
                    @error('external_ref')
                        <div class="text-red-500 text-xs mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label">Nome Esterno (Opzionale)</label>
                    <input type="text" name="external_name" class="pm-input" placeholder="es. Parcheggio Standard" />
                </div>
                <div class="pm-form-group" style="margin:0;">
                    <label class="pm-label pm-label-required">Prodotto Interno</label>
                    <select name="parking_product_id" required class="pm-select">
                        <option value="">Seleziona prodotto...</option>
                        @foreach ($platform->listings as $listing)
                            @if($listing->parking)
                                <optgroup label="{{ $listing->parking->name }}">
                                    @foreach ($listing->parking->products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }} (Capacità: {{ $product->capacity }})</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div style="margin:0;">
                    <button type="submit" class="pm-btn pm-btn-primary">Aggiungi Mapping</button>
                </div>
            </div>
        </form>
    </div>

</x-app-layout>