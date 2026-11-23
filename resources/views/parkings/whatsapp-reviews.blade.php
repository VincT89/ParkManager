<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">WhatsApp e recensioni</div>
            <div class="pm-page-subtitle">{{ $parking->name }}</div>
        </div>
        <a href="{{ route('parkings.index') }}" class="pm-btn pm-btn-secondary">Torna ai parcheggi</a>
    </x-slot>

    <div class="pm-settings-form pm-settings-page">
        <x-flash-message />

        <form class="pm-card pm-form" method="POST" action="{{ route('parkings.whatsapp-reviews.update', $parking) }}" autocomplete="off">
            @csrf
            @parkingContext
            @method('PUT')

            <div class="pm-form-group">
                <input type="hidden" name="enabled" value="0">
                <div class="pm-checkbox-group">
                    <input id="whatsapp-reviews-enabled" class="pm-checkbox" type="checkbox" name="enabled" value="1"
                           aria-describedby="review-timing" @checked(in_array(old('enabled', $parking->whatsappReviewsEnabled()), [true, 1, '1'], true))>
                    <label for="whatsapp-reviews-enabled" class="pm-checkbox-label">Attiva gli invii automatici</label>
                </div>
                <p id="review-timing" class="pm-form-note">Un invito per prenotazione, a partire da 24 ore dopo l’uscita registrata e con il consenso del cliente. Vale per le uscite registrate dopo l’attivazione.</p>
            </div>

            <div class="pm-form-group">
                <label for="review_url" class="pm-label">Link alla recensione Google</label>
                @php($reviewUrl = old('review_url', $settings['review_url'] ?? ''))
                <input id="review_url" class="pm-input" type="url" name="review_url" maxlength="512" aria-describedby="review-url-help"
                       value="{{ is_string($reviewUrl) ? $reviewUrl : '' }}">
                <p id="review-url-help" class="pm-form-note">Copia il link da «Ottieni più recensioni» nel Profilo dell’attività su Google.</p>
            </div>

            <fieldset class="pm-settings-section">
                <legend>Collegamento Twilio</legend>
                <p class="pm-form-note">Aggiungi il mittente WhatsApp al servizio di messaggistica Twilio e abilita la gestione delle rinunce (Advanced Opt-Out) con la parola STOP.</p>

                <div class="pm-settings-grid">
                    @foreach ([
                        'sender' => 'Numero mittente WhatsApp',
                        'messaging_service_sid' => 'Messaging Service SID',
                        'account_sid' => 'Account SID',
                        'api_key_sid' => 'API Key SID',
                        'content_sid' => 'Content SID del modello approvato',
                    ] as $name => $label)
                        @php($value = old($name, $settings[$name] ?? ''))
                        <div class="pm-form-group">
                            <label for="{{ $name }}" class="pm-label">{{ $label }}</label>
                            <input id="{{ $name }}" class="pm-input" type="{{ $name === 'sender' ? 'tel' : 'text' }}" name="{{ $name }}"
                                   value="{{ is_string($value) ? $value : '' }}" maxlength="{{ $name === 'sender' ? 16 : 34 }}" spellcheck="false">
                        </div>
                    @endforeach
                    <div class="pm-form-group">
                        <label for="twilio-api-key-secret" class="pm-label">API Key Secret</label>
                        <input id="twilio-api-key-secret" class="pm-input" type="password" name="credentials[api_key_secret]" maxlength="255"
                               autocomplete="new-password" aria-describedby="twilio-secret-help">
                        <p id="twilio-secret-help" class="pm-form-note">{{ $hasSecret ? 'Chiave salvata. Lascia vuoto per conservarla.' : 'Inserisci il segreto della chiave Twilio.' }}</p>
                    </div>
                </div>
            </fieldset>

            <div class="pm-form-group">
                <label for="review-template" class="pm-label">Testo del modello</label>
                <textarea id="review-template" class="pm-textarea" rows="8" readonly aria-describedby="review-template-help">{{ $message }}</textarea>
                <p id="review-template-help" class="pm-form-note">Usa questo testo nel modello da approvare su Twilio. Durante l’invio le variabili vengono sostituite con il nome del parcheggio e il link Google.</p>
            </div>

            <div class="pm-form-actions">
                <button class="pm-btn pm-btn-primary" type="submit">Salva impostazioni</button>
            </div>
        </form>
    </div>
</x-app-layout>
