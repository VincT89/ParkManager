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

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Canale / Piattaforma</label>
                    <select name="parking_listing_id" required class="pm-select">
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
                    <label class="pm-label">Categoria / Tipologia Posto</label>
                    <select name="parking_product_id" class="pm-select">
                        <option value="">Nessuna categoria (Legacy)</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}"
                                {{ old('parking_product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (€ {{ number_format($product->price, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pm-form-grid-3">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Nome cliente</label>
                    <input type="text" name="customer_name"
                           value="{{ old('customer_name') }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Email cliente</label>
                    <input type="email" name="customer_email"
                           value="{{ old('customer_email') }}" class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Telefono</label>
                    <input type="text" name="customer_phone"
                           value="{{ old('customer_phone') }}" class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Targa</label>
                    <input type="text" name="license_plate"
                           value="{{ old('license_plate') }}" class="pm-input" style="text-transform: uppercase;" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Rif. Volo</label>
                    <input type="text" name="flight_reference"
                           value="{{ old('flight_reference') }}" class="pm-input" placeholder="Es. AZ1602" style="text-transform: uppercase;" />
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Data arrivo</label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at') }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Data partenza</label>
                    <input type="datetime-local" name="ends_at"
                           value="{{ old('ends_at') }}" required class="pm-input" />
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Posti</label>
                    <input type="number" name="spots" min="1"
                           value="{{ old('spots', 1) }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Prezzo (€)</label>
                    <input type="number" name="price" min="0" step="0.01"
                           value="{{ old('price') }}" class="pm-input" />
                </div>
            </div>

            <div class="pm-form-group">
                <label class="pm-label">Note</label>
                <textarea name="notes" class="pm-textarea">{{ old('notes') }}</textarea>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Crea prenotazione</button>
                <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

</x-app-layout>