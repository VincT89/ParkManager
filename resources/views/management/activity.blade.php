<x-app-layout>
    <x-slot name="header">
        <div><div class="pm-page-title">Registro attività</div><div class="pm-page-subtitle">Chi ha eseguito le operazioni nel parcheggio selezionato.</div></div>
    </x-slot>
    @php
        $actions = ['created' => 'Creazione', 'updated' => 'Modifica', 'deleted' => 'Eliminazione', 'access_granted' => 'Accesso assegnato', 'access_revoked' => 'Accesso rimosso', 'role_changed' => 'Ruolo modificato'];
        $subjects = ['Reservation' => 'Prenotazione', 'Payment' => 'Pagamento', 'Parking' => 'Parcheggio', 'ParkingProduct' => 'Categoria', 'Platform' => 'Piattaforma', 'ParkingListing' => 'Collegamento', 'PlatformProductMapping' => 'Associazione categoria', 'AvailabilityBlock' => 'Blocco posti', 'ParkingCapacityAllocation' => 'Riserva di posti', 'User' => 'Utente'];
        $fields = [
            'name' => 'Nome', 'email' => 'Email', 'role' => 'Ruolo', 'is_active' => 'Stato di attivazione',
            'status' => 'Stato', 'capacity' => 'Numero di posti', 'capacity_mode' => 'Organizzazione dei posti', 'total_spots' => 'Posti totali',
            'price' => 'Tariffa', 'amount' => 'Importo', 'currency' => 'Valuta', 'spots' => 'Posti prenotati', 'notes' => 'Note',
            'customer_name' => 'Nome cliente', 'customer_email' => 'Email cliente', 'customer_phone' => 'Telefono cliente',
            'starts_at' => 'Data di inizio', 'ends_at' => 'Data di fine', 'expires_at' => 'Scadenza', 'paid_at' => 'Data del pagamento',
            'entered_at' => 'Ingresso', 'exited_at' => 'Uscita', 'has_entered' => 'Ingresso', 'has_exited' => 'Uscita',
            'license_plate' => 'Targa', 'flight_reference' => 'Volo', 'passengers_count' => 'Numero persone',
            'parking_id' => 'Parcheggio', 'management_id' => 'Gestione', 'parking_product_id' => 'Categoria', 'parking_listing_id' => 'Canale di prenotazione',
            'platform_id' => 'Piattaforma', 'reservation_id' => 'Prenotazione', 'provider' => 'Metodo di pagamento',
            'external_id' => 'Codice esterno', 'external_ref' => 'Codice sulla piattaforma', 'external_name' => 'Nome sulla piattaforma',
            'sort_order' => 'Ordine di visualizzazione', 'code' => 'Codice categoria', 'slug' => 'Codice piattaforma',
            'website' => 'Sito web', 'contact_email' => 'Email di contatto', 'allocation_type' => 'Destinazione dei posti',
            'uses_legacy_credentials' => 'Connessione condivisa', 'provider_payment_id' => 'Riferimento pagamento',
        ];
    @endphp
    <div class="pm-card">
        <div class="pm-table-wrapper" tabindex="0" role="region" aria-label="Operazioni registrate">
            <table class="pm-table">
                <thead><tr><th>Quando</th><th>Chi</th><th>Operazione</th><th>Elemento</th><th>Cosa è cambiato</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->timezone('Europe/Rome')->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $log->actor_name }}</td>
                            <td>{{ $actions[$log->action] ?? $log->action }}</td>
                            <td>{{ $subjects[$log->subject_type] ?? $log->subject_type }} {{ $log->subject_id }}</td>
                            <td>{{ collect($log->changes ?? [])->map(fn ($field) => $fields[$field] ?? ucfirst(str_replace('_', ' ', $field)))->implode(', ') ?: ($log->action === 'created' ? 'Elemento creato' : ($log->action === 'deleted' ? 'Elemento rimosso' : 'Operazione registrata')) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nessuna attività registrata per questo parcheggio.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px">{{ $logs->links() }}</div>
    </div>
</x-app-layout>
