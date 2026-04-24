@extends('layouts.public')

@section('content')
    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-weight: 500;">
            {{ session('error') }}
        </div>
    @endif

    <div class="pm-card">
        <div class="pm-card-header">
            <h2 class="pm-card-title">Inserisci i Dati della Prenotazione</h2>
        </div>

        <div style="padding: 24px;">
            <form id="booking-form" action="{{ route('public.booking.store') }}" method="POST">
                @csrf
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div class="pm-form-group" style="margin-bottom: 0;">
                        <label class="pm-label">Data e Ora di Arrivo</label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="pm-input" value="{{ old('starts_at') }}" required>
                        @error('starts_at') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                    </div>
                    <div class="pm-form-group" style="margin-bottom: 0;">
                        <label class="pm-label">Data e Ora di Partenza</label>
                        <input type="datetime-local" name="ends_at" id="ends_at" class="pm-input" value="{{ old('ends_at') }}" required>
                        @error('ends_at') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
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

                        <div class="pm-form-group">
                            <label class="pm-label">Targa Veicolo</label>
                            <input type="text" name="license_plate" class="pm-input" value="{{ old('license_plate') }}" required style="text-transform: uppercase;">
                            @error('license_plate') <span style="color: #991b1b; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
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
                        starts_at: document.getElementById('starts_at').value,
                        ends_at: document.getElementById('ends_at').value,
                        spots: document.getElementById('spots').value
                    })
                });
                
                const data = await response.json();
                
                resultBox.style.display = 'block';
                
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
        const inputs = ['starts_at', 'ends_at', 'product_code', 'spots'];
        inputs.forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => {
                document.getElementById('availability-result').style.display = 'none';
                document.getElementById('customer-details').style.display = 'none';
            });
        });
    </script>
@endsection
