<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Nuovo blocco disponibilità</div>
            <div class="pm-page-subtitle">chiusura, manutenzione o blocco manuale</div>
        </div>
        <a href="{{ route('availability-blocks.index') }}" class="pm-btn pm-btn-secondary">
            Torna alla lista
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('availability-blocks.store') }}" class="pm-form">
            @csrf

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Parcheggio</label>
                    <select name="parking_id" required class="pm-select">
                        <option value="">Seleziona parcheggio...</option>
                        @foreach ($parkings as $parking)
                            <option value="{{ $parking->id }}"
                                {{ old('parking_id') == $parking->id ? 'selected' : '' }}>
                                {{ $parking->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">
                        Canale specifico
                        <span class="pm-text-muted" style="font-weight:400;text-transform:none;letter-spacing:0">
                            (vuoto = tutti)
                        </span>
                    </label>
                    <select name="parking_listing_id" class="pm-select">
                        <option value="">Tutti i canali</option>
                        @foreach ($listings as $listing)
                            <option value="{{ $listing->id }}"
                                {{ old('parking_listing_id') == $listing->id ? 'selected' : '' }}>
                                {{ $listing->platform->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Tipo blocco</label>
                    <select name="type" required class="pm-select">
                        <option value="">Seleziona tipo...</option>
                        @foreach ($blockTypes as $type)
                            <option value="{{ $type->value }}"
                                {{ old('type') == $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Posti bloccati</label>
                    <input type="number" name="spots" min="1"
                           value="{{ old('spots', 1) }}" required class="pm-input" />
                </div>
            </div>

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Dal</label>
                    <input type="datetime-local" name="starts_at"
                           value="{{ old('starts_at') }}" required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Al</label>
                    <input type="datetime-local" name="ends_at"
                           value="{{ old('ends_at') }}" required class="pm-input" />
                </div>
            </div>

            <div class="pm-form-group">
                <label class="pm-label">Motivo</label>
                <textarea name="reason" class="pm-textarea"
                          placeholder="Descrivi il motivo del blocco...">{{ old('reason') }}</textarea>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Crea blocco</button>
                <a href="{{ route('availability-blocks.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

</x-app-layout>