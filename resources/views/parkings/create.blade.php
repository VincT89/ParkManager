<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Nuovo parcheggio</div>
            <div class="pm-page-subtitle">Imposta i posti disponibili e chi potrà gestirlo.</div>
        </div>
        <a href="{{ route('parkings.index') }}" class="pm-btn pm-btn-secondary">Torna ai parcheggi</a>
    </x-slot>

    <div class="pm-settings-form">
        @if($errors->any())
            <div class="pm-form-errors" role="alert"><p>Controlla i campi indicati e riprova.</p>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="POST" action="{{ route('parkings.store') }}" class="pm-card pm-form" x-data="{ separate: @js((bool) old('separate_management', true)) }">
            @csrf
            @parkingContext
            <div class="pm-settings-grid">
                <div class="pm-form-group">
                    <label class="pm-label" for="name">Nome del parcheggio</label>
                    <input id="name" name="name" type="text" class="pm-input" value="{{ old('name') }}" maxlength="255" required>
                    @error('name') <p class="pm-form-error">{{ $message }}</p> @enderror
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="total_spots">Numero totale di posti</label>
                    <input id="total_spots" name="total_spots" type="number" class="pm-input" value="{{ old('total_spots') }}" min="1" required aria-describedby="total-spots-help">
                    <p id="total-spots-help" class="pm-form-note">Quanti veicoli può ospitare il parcheggio.</p>
                    @error('total_spots') <p class="pm-form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <x-parking-capacity-field :value="old('capacity_mode', 'shared')" />

            <fieldset class="pm-settings-section">
                <legend>Chi può accedere a questo parcheggio?</legend>
                <input type="hidden" name="separate_management" value="0">
                <div class="pm-checkbox-group">
                    <input id="separate-management" name="separate_management" type="checkbox" class="pm-checkbox" value="1" x-model="separate" @checked(old('separate_management', true)) aria-describedby="management-access-help">
                    <label for="separate-management">Crea una gestione con accessi separati</label>
                </div>
                <p id="management-access-help" class="pm-form-note" x-text="separate ? 'All’inizio avrai accesso solo tu. Potrai aggiungere il socio e gli operatori dalla pagina Utenti.' : 'Avranno accesso gli utenti della gestione corrente. Prenotazioni e disponibilità resteranno distinte per parcheggio.'">All’inizio avrai accesso solo tu. Potrai aggiungere il socio e gli operatori dalla pagina Utenti.</p>
                <div class="pm-form-group" x-show="separate">
                    <label class="pm-label" for="management_name">Nome della nuova gestione</label>
                    <input id="management_name" name="management_name" type="text" class="pm-input" value="{{ old('management_name') }}" maxlength="255" :required="separate" :disabled="!separate" aria-describedby="management-name-help">
                    <p id="management-name-help" class="pm-form-note">Identifica la società o il gruppo che lo gestisce. Può coincidere con il nome del parcheggio.</p>
                    @error('management_name') <p class="pm-form-error">{{ $message }}</p> @enderror
                </div>
                <p x-show="!separate" class="pm-form-note">Gestione corrente: <strong>{{ $activeManagement->name }}</strong>.</p>
            </fieldset>

            <div class="pm-checkbox-group">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" class="pm-checkbox" value="1" @checked(old('is_active', true))>
                <label for="is_active">Parcheggio attivo</label>
            </div>
            <p class="pm-form-note">Dopo la creazione potrai impostare le categorie e le tariffe.</p>
            <div class="pm-settings-actions">
                <a href="{{ route('parkings.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
                <button type="submit" class="pm-btn pm-btn-primary">Crea parcheggio</button>
            </div>
        </form>
    </div>
</x-app-layout>
