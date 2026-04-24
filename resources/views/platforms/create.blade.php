<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Nuova piattaforma</div>
            <div class="pm-page-subtitle">aggiungi un canale di vendita</div>
        </div>
        <a href="{{ route('platforms.index') }}" class="pm-btn pm-btn-secondary">
            Torna alla lista
        </a>
    </x-slot>

    <x-flash-message />

    <div class="pm-card pm-animate" style="max-width:720px">
        <form method="POST" action="{{ route('platforms.store') }}" class="pm-form">
            @csrf

            <div class="pm-form-grid-2">
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Nome</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           required class="pm-input" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label pm-label-required">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                           required class="pm-input" placeholder="es. parking-my-car" />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Sito web</label>
                    <input type="url" name="website" value="{{ old('website') }}"
                           class="pm-input" placeholder="https://..." />
                </div>
                <div class="pm-form-group">
                    <label class="pm-label">Email contatto</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}"
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