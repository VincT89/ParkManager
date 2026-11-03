<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Nuova prenotazione</div>
            <div class="pm-page-subtitle">inserimento manuale</div>
        </div>
        <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">
            Torna alla lista
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('reservations.store') }}" class="pm-form">
            @csrf
            @parkingContext

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-parking_listing_id">Canale / Piattaforma</label>
                    <select id="reservation-parking_listing_id" name="parking_listing_id" required class="pm-select">
                        <option value="">Seleziona canale...</option>
                        @foreach ($listings as $listing)
                            <option value="{{ $listing->id }}"
                                {{ old('parking_listing_id') == $listing->id ? 'selected' : '' }}>
                                {{ $listing->platform->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-parking_product_id">Categoria / Tipologia Posto</label>
                    <select id="reservation-parking_product_id" name="parking_product_id" class="pm-select">
                        <option value="">Nessuna categoria</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                {{ old('parking_product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (€ {{ number_format($product->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pm-form-group">
                <label class="pm-label" for="reservation-is_valet">Tipo di servizio</label>
                <select id="reservation-is_valet" name="is_valet" class="pm-select" required>
                    <option value="0" @selected(in_array(old('is_valet', 0), [0, '0', false], true))>Parcheggio standard</option>
                    <option value="1" @selected(in_array(old('is_valet', 0), [1, '1', true], true))>Car valet</option>
                </select>
                @error('is_valet') <span class="pm-form-error">{{ $message }}</span> @enderror
            </div>

            <div class="pm-form-grid-3">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-customer_name">Nome cliente</label>
                    <input type="text" id="reservation-customer_name" name="customer_name"
                           value="{{ old('customer_name') }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-customer_email">Email cliente</label>
                    <input type="email" id="reservation-customer_email" name="customer_email"
                           value="{{ old('customer_email') }}" class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-customer_phone">Telefono</label>
                    <input type="text" id="reservation-customer_phone" name="customer_phone"
                           value="{{ old('customer_phone') }}" class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-license_plate">Targa</label>
                    <input type="text" id="reservation-license_plate" name="license_plate"
                           value="{{ old('license_plate') }}" class="pm-input" style="text-transform: uppercase;" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-flight_reference">Rif. Volo</label>
                    <input type="text" id="reservation-flight_reference" name="flight_reference"
                           value="{{ old('flight_reference') }}" class="pm-input" placeholder="Es. AZ1602" style="text-transform: uppercase;" />
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-starts_at">Data arrivo</label>
                    <input type="datetime-local" id="reservation-starts_at" name="starts_at"
                           value="{{ old('starts_at') }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-ends_at">Data partenza</label>
                    <input type="datetime-local" id="reservation-ends_at" name="ends_at"
                           value="{{ old('ends_at') }}" required class="pm-input" />
                </div>
            </div>

            <div class="pm-form-grid-3">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-spots">Posti</label>
                    <input type="number" id="reservation-spots" name="spots" min="1"
                           value="{{ old('spots', 1) }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-passengers_count">Numero persone</label>
                    <input type="number" id="reservation-passengers_count" name="passengers_count" min="1" max="9"
                           value="{{ old('passengers_count', 1) }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-price">Prezzo (€)</label>
                    <input type="number" id="reservation-price" name="price" min="0" step="0.01"
                           value="{{ old('price') }}" class="pm-input" />
                </div>
            </div>

            <div class="pm-form-group">
                <label class="pm-label" for="reservation-notes">Note</label>
                <textarea id="reservation-notes" name="notes" class="pm-textarea">{{ old('notes') }}</textarea>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Crea prenotazione</button>
                <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

</x-app-layout>
