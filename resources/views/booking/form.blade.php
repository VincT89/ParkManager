@extends('layouts.public')

@section('content')
    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-weight: 500;">
            {{ session('error') }}
        </div>
    @endif

    <div style="background: #fffbeb; border-left: 4px solid #f59e0b; color: #b45309; padding: 16px; border-radius: 8px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
        <div style="font-size: 15px; line-height: 1.5; width: 100%;">
            <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">Avviso alla Clientela:</div>
            <div>Ricordiamo che è necessario presentarsi in struttura con almeno <strong style="text-decoration: underline;">2 ore di anticipo</strong> rispetto all'orario del proprio volo.</div>
            <div style="margin-top: 12px;">
                Invitiamo inoltre a leggere attentamente i nostri <a href="/termini-e-condizioni.pdf" target="_blank" style="color: #92400e; text-decoration: underline; font-weight: 600;">Termini e Condizioni</a>.
            </div>
        </div>
    </div>

    <div class="pm-card">
        <div class="pm-card-header">
            <h2 class="pm-card-title">Inserisci i Dati della Prenotazione</h2>
        </div>

        <div style="padding: 24px;">
            <form id="booking-form" action="{{ route('public.booking.store') }}" method="POST">
                @csrf
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div class="pm-form-group" style="margin-bottom: 0; display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div>
                            <label class="pm-label">Data arrivo</label>
                            <input type="date" name="arrival_date" id="arrival_date" class="pm-input" value="{{ old('arrival_date') }}" required>
                            @error('arrival_date') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="pm-label">Ora arrivo</label>
                            <input type="time" name="arrival_time" id="arrival_time" class="pm-input" value="{{ old('arrival_time') }}" required>
                            @error('arrival_time') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="pm-form-group" style="margin-bottom: 0; display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                        <div>
                            <label class="pm-label">Data partenza</label>
                            <input type="date" name="departure_date" id="departure_date" class="pm-input" value="{{ old('departure_date') }}" required>
                            @error('departure_date') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="pm-label">Ora partenza</label>
                            <input type="time" name="departure_time" id="departure_time" class="pm-input" value="{{ old('departure_time') }}" required>
                            @error('departure_time') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>


                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div class="pm-form-group" style="margin-bottom: 0;">
                        <label class="pm-label">Prodotto / Tipo Veicolo</label>
                        <select name="product_code" id="product_code" class="pm-select" required>
                            @foreach($products as $prod)
                                <option value="{{ $prod->code }}" {{ old('product_code') == $prod->code ? 'selected' : '' }}>
                                    {{ $prod->name }} - {{ number_format($prod->price, 2, ',', '.') }} € / giorno
                                </option>
                            @endforeach
                        </select>
                        @error('product_code') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                    </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Numero Posti</label>
                            <input type="number" name="spots" id="spots" class="pm-input" min="1" max="10" value="{{ old('spots', 1) }}" required>
                            @error('spots') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; padding: 16px; background: #f8fafc; border: 1px solid var(--pm-border); border-radius: 8px;">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 4px;">Verifica Disponibilità</div>
                            <div style="font-size: 13px; color: var(--pm-text-muted);">Controlla se c'è posto per le date selezionate.</div>
                        </div>
                        <button type="button" id="btn-check" class="pm-btn pm-btn-secondary">Controlla</button>
                    </div>

                    <div id="availability-result" style="display: none; padding: 16px; border-radius: 8px; margin-bottom: 24px;"></div>

                    <div id="customer-details" style="display: none; border-top: 1px solid var(--pm-border); padding-top: 24px;">
                        <h3 style="font-size: 16px; font-weight: 600; margin-top: 0; margin-bottom: 16px;">I tuoi dati</h3>
                        
                        <div class="pm-form-group">
                            <label class="pm-label">Nome Completo</label>
                            <input type="text" name="customer_name" class="pm-input" value="{{ old('customer_name') }}" required>
                            @error('customer_name') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div class="pm-form-group">
                                <label class="pm-label">Email</label>
                                <input type="email" name="customer_email" class="pm-input" value="{{ old('customer_email') }}" required>
                                @error('customer_email') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                            </div>
                            <div class="pm-form-group">
                                <label class="pm-label">Telefono</label>
                                <input type="text" name="customer_phone" class="pm-input" value="{{ old('customer_phone') }}" required>
                                @error('customer_phone') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div class="pm-form-group">
                                <label class="pm-label">Targa Veicolo</label>
                                <input type="text" name="license_plate" class="pm-input" value="{{ old('license_plate') }}" required style="text-transform: uppercase;">
                                @error('license_plate') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                            </div>
                            <div class="pm-form-group">
                                <label for="flight_reference" class="pm-label">Riferimento Volo</label>
                                <input type="text" id="flight_reference" name="flight_reference" class="pm-input" value="{{ old('flight_reference') }}" placeholder="Es. AZ1602" maxlength="20" style="text-transform: uppercase;">
                                @error('flight_reference') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div style="text-align: right; margin-top: 24px;">
                            <button type="submit" class="pm-btn pm-btn-primary" style="padding: 12px 24px; font-size: 16px;">Conferma Prenotazione</button>
                        </div>
                    </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('btn-check').addEventListener('click', async function() {
            const btn = this;
            const resultBox = document.getElementById('availability-result');
            const customerForm = document.getElementById('customer-details');
            
            btn.disabled = true;
            btn.textContent = 'Controllo in corso...';
            
            try {
                const response = await fetch('{{ route('public.booking.check') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_code: document.getElementById('product_code').value,
                        arrival_date: document.getElementById('arrival_date').value,
                        arrival_time: document.getElementById('arrival_time').value,
                        departure_date: document.getElementById('departure_date').value,
                        departure_time: document.getElementById('departure_time').value,
                        spots: document.getElementById('spots').value
                    })
                });
                
                const data = await response.json();
                
                resultBox.style.display = 'block';

                if (!response.ok) {
                    let errMsg = data.message || 'Si è verificato un errore.';
                    if (response.status === 422 && data.errors) {
                        errMsg = Object.values(data.errors).flat().join('<br>');
                    }
                    resultBox.className = 'pm-avail-no';
                    resultBox.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 4px; color: #991b1b;">✗ Attenzione</div>
                        <div style="font-size: 14px; color: #991b1b;">${errMsg}</div>
                    `;
                    customerForm.style.display = 'none';
                    return;
                }
                
                if (data.available) {
                    resultBox.className = 'pm-avail-ok';
                    resultBox.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 4px;">✓ Disponibile!</div>
                        <div style="font-size: 14px;">Il costo totale previsto è di <strong>${data.price_formatted}</strong>. Procedi con i tuoi dati per confermare.</div>
                    `;
                    customerForm.style.display = 'block';
                } else {
                    resultBox.className = 'pm-avail-no';
                    resultBox.innerHTML = `
                        <div style="font-weight: 600; margin-bottom: 4px;">✗ Non Disponibile</div>
                        <div style="font-size: 14px;">${data.reason || 'Siamo spiacenti, i posti richiesti non sono disponibili.'}</div>
                    `;
                    customerForm.style.display = 'none';
                }
            } catch (error) {
                console.error(error);
                alert('Errore di connessione. Riprova più tardi.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Controlla';
            }
        });

        // Hide customer form if inputs change
        const inputs = ['arrival_date', 'arrival_time', 'departure_date', 'departure_time', 'product_code', 'spots'];
        inputs.forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                document.getElementById('availability-result').style.display = 'none';
                document.getElementById('customer-details').style.display = 'none';
            });
        });
    </script>
@endsection
