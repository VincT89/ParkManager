<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Nuova piattaforma</div>
            <div class="pm-page-subtitle">Aggiungi un canale da cui ricevere le prenotazioni.</div>
        </div>
        <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">
            Torna alle piattaforme
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('platforms.store') }}" class="pm-form">
            @csrf
            @parkingContext

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="platform-name">Nome della piattaforma</label>
                    <input type="text" id="platform-name" name="name" value="{{ old('name') }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required" for="platform-slug">Codice piattaforma</label>
                    <input type="text" id="platform-slug" name="slug" list="platform-codes" aria-describedby="platform-code-help" value="{{ old('slug') }}"
                           required class="pm-input" placeholder="es. parking-my-car" />
                </div>
                <datalist id="platform-codes"><option value="parkos">Parkos</option><option value="parking-my-car">ParkingMyCar</option><option value="vologio">Vologio</option><option value="website">Sito web</option></datalist>
                <p id="platform-code-help" class="pm-form-note" style="grid-column:1 / -1">Il codice collega la piattaforma alla sua integrazione. Seleziona un valore dall’elenco per usare uno dei collegamenti disponibili.</p>
                <div class="pm-form-group">
                    <label class="pm-label" for="platform-website">Sito web</label>
                    <input type="url" id="platform-website" name="website" value="{{ old('website') }}"
                           class="pm-input" placeholder="https://..." />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label" for="platform-contact_email">Email di contatto</label>
                    <input type="email" id="platform-contact_email" name="contact_email" value="{{ old('contact_email') }}"
                           class="pm-input" />
                </div>
            </div>



            <div class="pm-checkbox-group">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       {{ old('is_active', true) ? 'checked' : '' }}
                       class="pm-checkbox" />
                <label for="is_active" class="pm-checkbox-label">Piattaforma attiva</label>
            </div>

            <div class="pm-form-actions">
                <button type="submit" class="pm-btn pm-btn-primary">Crea piattaforma</button>
                <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">Annulla</a>
            </div>

        </form>
    </div>

</x-app-layout>
