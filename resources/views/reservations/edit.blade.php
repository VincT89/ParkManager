<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Modifica prenotazione</div>
            <div class="pm-page-subtitle">{{ $reservation->customer_name }}</div>
        </div>
        <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">
            Torna alla lista
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="pm-form">
            @csrf
            @parkingContext
            @method('PUT')

            <div class="pm-form-group" style="margin-bottom:24px;">
                <label class="pm-label" for="reservation-parking_product_id">Categoria / Tipologia Posto</label>
                <select id="reservation-parking_product_id" name="parking_product_id" class="pm-select">
                    <option value="">Nessuna categoria</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            {{ old('parking_product_id', $reservation->parking_product_id) == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (€ {{ number_format($product->price, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="pm-form-group">
                <label class="pm-label" for="reservation-is_valet">Tipo di servizio</label>
                @php($selectedValet = old('is_valet', $reservation->is_valet === null ? '' : (int) $reservation->is_valet))
                <select id="reservation-is_valet" name="is_valet" class="pm-select">
                    <option value="" @selected($selectedValet === null || $selectedValet === '')>Non specificato</option>
                    <option value="0" @selected(in_array($selectedValet, [0, '0', false], true))>Parcheggio standard</option>
                    <option value="1" @selected(in_array($selectedValet, [1, '1', true], true))>Car valet</option>
                </select>
                @error('is_valet') <span class="pm-form-error">{{ $message }}</span> @enderror
            </div>

            <div class="pm-form-grid-3">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-customer_name">Nome cliente</label>
                    <input type="text" id="reservation-customer_name" name="customer_name"
                           value="{{ old('customer_name', $reservation->customer_name) }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-customer_email">Email cliente</label>
                    <input type="email" id="reservation-customer_email" name="customer_email"
                           value="{{ old('customer_email', $reservation->customer_email) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-customer_phone">Telefono</label>
                    <input type="text" id="reservation-customer_phone" name="customer_phone"
                           value="{{ old('customer_phone', $reservation->customer_phone) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-license_plate">Targa</label>
                    <input type="text" id="reservation-license_plate" name="license_plate"
                           value="{{ old('license_plate', $reservation->license_plate) }}"
                           class="pm-input" style="text-transform: uppercase;" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-flight_reference">Rif. Volo</label>
                    <input type="text" id="reservation-flight_reference" name="flight_reference"
                           value="{{ old('flight_reference', $reservation->flight_reference) }}"
                           class="pm-input" placeholder="Es. AZ1602" style="text-transform: uppercase;" />
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-starts_at">Data arrivo</label>
                    <input type="datetime-local" id="reservation-starts_at" name="starts_at" required
                           value="{{ old('starts_at', $reservation->starts_at->format('Y-m-d\TH:i')) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-ends_at">Data partenza</label>
                    <input type="datetime-local" id="reservation-ends_at" name="ends_at" required
                           value="{{ old('ends_at', $reservation->ends_at->format('Y-m-d\TH:i')) }}"
                           class="pm-input" />
                </div>
            </div>

            <div class="pm-form-grid-4">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-spots">Posti</label>
                    <input type="number" id="reservation-spots" name="spots" min="1" required
                           value="{{ old('spots', $reservation->spots) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-passengers_count">Numero persone</label>
                    <input type="number" id="reservation-passengers_count" name="passengers_count" min="1" max="9" required
                           value="{{ old('passengers_count', $reservation->passengers_count ?? 1) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="reservation-price">Prezzo (€)</label>
                    <input type="number" id="reservation-price" name="price" min="0" step="0.01"
                           value="{{ old('price', $reservation->price) }}"
                           class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="reservation-status">Stato</label>
                    <select id="reservation-status" name="status" required class="pm-select">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}"
                                {{ old('status', $reservation->status->value) === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pm-form-group">
                <label class="pm-label" for="reservation-notes">Note</label>
                <textarea id="reservation-notes" name="notes" class="pm-textarea">{{ old('notes', $reservation->notes) }}</textarea>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Salva modifiche</button>
                <a href="{{ route('reservations.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

</x-app-layout>
