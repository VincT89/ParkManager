<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <a href="{{ route('parkings.index') }}" class="pm-btn pm-btn-secondary pm-btn-sm pm-mb-8">
                    &larr; Torna alla lista
                </a>
                <div class="pm-page-title">Configurazione: {{ $parking->name }}</div>
                <div class="pm-page-subtitle">Gestione fisica, inventario vendibile e posti riservati</div>
            </div>
        </div>
    </x-slot>

    <div class="pm-animate">
        <x-flash-message />

        @if ($errors->any())
            <div class="pm-card pm-alert-row danger" style="margin-bottom: 24px;">
                <div style="font-weight: 600; margin-bottom: 8px;">Errore di Validazione:</div>
                <ul style="list-style-type: disc; padding-left: 20px; font-size: 14px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form 1: Dati Generali Parcheggio -->
        <form action="{{ route('parkings.update', $parking) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="pm-card" style="margin-bottom: 24px;">
                <div class="pm-card-header" style="justify-content: space-between;">
                    <div class="pm-card-title">1. Dati Generali Parcheggio</div>
                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">
                        Salva Dati Base
                    </button>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 24px;">
                    <div class="pm-form-group">
                        <label class="pm-label">Nome Parcheggio</label>
                        <input type="text" name="name" class="pm-input" value="{{ old('name', $parking->name) }}" required>
                    </div>

                    <div class="pm-form-group">
                        <label class="pm-label">Posti Fisici Totali</label>
                        <input type="number" id="base_total_spots" name="total_spots" class="pm-input" style="font-family: var(--pm-mono);" value="{{ old('total_spots', $parking->total_spots) }}" min="1" required>
                        <div style="font-size: 11px; color: var(--pm-text-muted); margin-top: 6px; line-height: 1.4;">
                            Limite amministrativo di scudo, non usato come disponibilità in modalità per-product.
                        </div>
                    </div>

                    <div class="pm-form-group">
                        <label class="pm-label">Gestione Capacità</label>
                        <select name="capacity_mode" class="pm-select">
                            <option value="shared" {{ old('capacity_mode', $parking->capacity_mode) === 'shared' ? 'selected' : '' }}>Shared (Pool Unico)</option>
                            <option value="per_product" {{ old('capacity_mode', $parking->capacity_mode) === 'per_product' ? 'selected' : '' }}>Per Product (Aree Separate)</option>
                        </select>
                        <div style="font-size: 11px; color: var(--pm-text-muted); margin-top: 6px;">"Shared" protegge la capienza totale. "Per product" ignora la fisica e blocchi globali.</div>
                    </div>

                    <div class="pm-form-group">
                        <label class="pm-label">Stato Parcheggio</label>
                        <select name="is_active" class="pm-select">
                            <option value="1" {{ old('is_active', $parking->is_active) ? 'selected' : '' }}>ATTIVO</option>
                            <option value="0" {{ old('is_active', $parking->is_active) ? '' : 'selected' }}>INATTIVO</option>
                        </select>
                        <div style="font-size: 11px; color: var(--pm-amber); margin-top: 6px;">Disattivarlo ibernerebbe l'intero parcheggio.</div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form 2: Prodotti / Categorie Vendibili -->
        <form action="{{ route('parkings.products.upsert', $parking) }}" method="POST" id="config-form">
            @csrf
            @method('PUT')

            <div class="pm-card" style="margin-bottom: 24px;">
                <div class="pm-card-header" style="justify-content: space-between;">
                    <div class="pm-card-title">2. Prodotti / Categorie Vendibili</div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="generateBasicSetup()">Setup Base</button>
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="addProductRow()">+ Aggiungi Categoria</button>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--pm-border); color: var(--pm-text-muted);">
                                <th style="padding: 12px 8px; font-weight: 500;">Nome</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Codice (snake_case)</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Capacità</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Prezzo</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Ordine</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Attivo</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Azione</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            <!-- Injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Dashboard Riepilogo Live -->
                <div id="live-validation-panel" style="margin-top: 16px; padding: 16px; border: 1px solid var(--pm-border); border-radius: var(--pm-radius); transition: all 0.2s;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px; font-size:14px;">Riepilogo Capacità Prodotti</div>
                            <div id="live-validation-msg" style="font-size: 13px; color: var(--pm-text-muted);">...</div>
                        </div>
                        <div style="display: flex; gap: 24px; align-items: center;">
                            <div style="text-align: right;">
                                <div style="font-size: 11px; text-transform: uppercase; color: var(--pm-text-dim);">Capienza Fisica</div>
                                <div id="hud-total" style="font-size: 20px; font-weight: 600; font-family: var(--pm-mono);">0</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; text-transform: uppercase; color: var(--pm-text-dim);">Allocata Attiva</div>
                                <div id="hud-allocated" style="font-size: 20px; font-weight: 600; font-family: var(--pm-mono);">0</div>
                            </div>
                            <div style="text-align: right; padding-left: 16px; border-left: 1px solid var(--pm-border);">
                                <div style="font-size: 11px; text-transform: uppercase; color: var(--pm-text-dim);">Differenza</div>
                                <div id="hud-diff" style="font-size: 20px; font-weight: 600; font-family: var(--pm-mono);">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div style="display: flex; justify-content: flex-end; gap: 16px; align-items: center; margin-top: 16px;">
                    <div id="submit-warning" style="color: var(--pm-red); font-size: 13px; font-weight: 500; display: none;">
                        Risolvi l'errore di capienza per poter salvare i prodotti.
                    </div>
                    <button type="submit" id="main-submit-btn" class="pm-btn pm-btn-primary" style="padding: 10px 24px;">
                        Salva Prodotti
                    </button>
                </div>
            </div>
        </form>

        <!-- Form 3: Allocazioni (Sequenza B) -->
        <div class="pm-card" style="margin-bottom: 24px;">
            <div class="pm-card-header" style="justify-content: space-between;">
                <div class="pm-card-title">3. Posti Riservati (Allocazioni Capacità)</div>
                @if($parking->capacity_mode === 'shared')
                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="document.getElementById('new-allocation-form').style.display='block'">
                    + Nuova Allocazione Globale
                </button>
                @endif
            </div>

            @if($parking->capacity_mode === 'per_product')
            <div class="pm-card pm-alert-row" style="margin-top: 16px; margin-bottom: 16px; background-color: var(--pm-bg); border: 1px solid var(--pm-border);">
                <div style="font-weight: 500; font-size: 14px;">Funzionalità Disabilitata</div>
                <div style="font-size: 13px; color: var(--pm-text-muted); margin-top: 4px;">
                    Le allocazioni globali non sono ammesse quando il parcheggio è configurato in modalità <strong>Per Product</strong>. Ogni blocco o allocazione deve essere applicato direttamente a uno specifico prodotto o categoria, altrimenti non influenzerebbe la disponibilità di vendita.
                </div>
            </div>
            @endif

            <!-- New Allocation Form -->
            <div id="new-allocation-form" style="display: none; background: var(--pm-bg); padding: 16px; border: 1px solid var(--pm-border); border-radius: var(--pm-radius); margin-bottom: 16px;">
                <h4 style="margin-top: 0; margin-bottom: 12px; font-weight: 600; font-size: 14px;">Aggiungi Posti Riservati</h4>
                <form action="{{ route('parkings.allocations.store', $parking->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="parking_id" value="{{ $parking->id }}">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; align-items: end;">
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Tipo Destinazione</label>
                            <select name="allocation_type" class="pm-select" required>
                                <option value="rentcar">Rent Car</option>
                                <option value="internal_use">Uso Interno</option>
                                <option value="partner">Partner Commerciale</option>
                                <option value="maintenance">Manutenzione</option>
                                <option value="other">Altro</option>
                            </select>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Numero Posti</label>
                            <input type="number" name="spots" class="pm-input" min="1" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Note / Riferimento</label>
                            <input type="text" name="notes" class="pm-input" placeholder="Es. Hertz furgoni">
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Data Inizio (Inclusa)</label>
                            <input type="datetime-local" name="starts_at" class="pm-input" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label">Data Fine (Esclusa)</label>
                            <input type="datetime-local" name="ends_at" class="pm-input" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label pm-flex pm-items-center" style="gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" checked style="width:18px; height:18px;">
                                <span>Attiva Subito</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 16px; text-align: right;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('new-allocation-form').style.display='none'">Annulla</button>
                        <button type="submit" class="pm-btn pm-btn-primary">Salva Allocazione</button>
                    </div>
                </form>
            </div>

            <!-- Allocations List -->
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--pm-border); color: var(--pm-text-muted);">
                            <th style="padding: 12px 8px; font-weight: 500;">Stato</th>
                            <th style="padding: 12px 8px; font-weight: 500;">Destinazione</th>
                            <th style="padding: 12px 8px; font-weight: 500;">Posti</th>
                            <th style="padding: 12px 8px; font-weight: 500;">Inizio</th>
                            <th style="padding: 12px 8px; font-weight: 500;">Fine</th>
                            <th style="padding: 12px 8px; font-weight: 500;">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parking->allocations as $alloc)
                            <tr style="border-bottom: 1px solid var(--pm-border-light); {{ !$alloc->is_active ? 'opacity: 0.5;' : '' }}">
                                <td style="padding: 12px 8px;">
                                    @if($alloc->is_active && $alloc->ends_at > now())
                                        <span class="pm-badge green">Attivo</span>
                                    @elseif($alloc->ends_at <= now())
                                        <span class="pm-badge gray">Scaduto</span>
                                    @else
                                        <span class="pm-badge gray">Disattivo</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 8px;">
                                    <div style="font-weight: 500;">{{ strtoupper($alloc->allocation_type) }}</div>
                                    <div style="font-size: 12px; color: var(--pm-text-muted);">{{ $alloc->notes }}</div>
                                </td>
                                <td style="padding: 12px 8px;">
                                    <span class="pm-badge gray">{{ $alloc->spots }}</span>
                                </td>
                                <td style="padding: 12px 8px; font-family: var(--pm-mono); font-size: 13px;">
                                    {{ $alloc->starts_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="padding: 12px 8px; font-family: var(--pm-mono); font-size: 13px;">
                                    {{ $alloc->ends_at->format('d/m/Y H:i') }}
                                </td>
                                <td style="padding: 12px 8px;">
                                    <form action="{{ route('parkings.allocations.destroy', ['parking' => $parking->id, 'allocation' => $alloc->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Eliminare fisicamente questa allocazione?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm" style="padding: 4px 8px; font-size: 11px;">Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="pm-text-muted" style="text-align: center; padding: 24px;">Nessun posto riservato / allocazione configurata.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

<script>
    // Server data payload
    const initialProducts = @json(old('products', $parking->products));
    let productIndex = 0;

    const tbody = document.getElementById('products-tbody');
    const totalSpotsInput = document.getElementById('base_total_spots');
    
    // HUD Elements
    const hudPanel = document.getElementById('live-validation-panel');
    const hudTotal = document.getElementById('hud-total');
    const hudAllocated = document.getElementById('hud-allocated');
    const hudDiff = document.getElementById('hud-diff');
    const hudMsg = document.getElementById('live-validation-msg');
    const submitBtn = document.getElementById('main-submit-btn');
    const submitWarn = document.getElementById('submit-warning');

    document.addEventListener('DOMContentLoaded', () => {
        if (initialProducts && initialProducts.length > 0) {
            initialProducts.forEach(p => appendProductRow(p));
        }
        
        totalSpotsInput.addEventListener('input', updateHUD);
        updateHUD();
    });

    function generateBasicSetup() {
        if (confirm("Attenzione: Selezionando questa opzione verranno caricate le 4 categorie standard. I prodotti precedenti non saranno cancellati dal server a meno che non premi il cestino ed esegui il Salva.\n\nVuoi procedere inserendo i nuovi campi?")) {
            appendProductRow({name: 'Auto Scoperto', code: 'auto_open', capacity: 1000, price: 5.0, is_active: 1});
            appendProductRow({name: 'Auto Coperto', code: 'auto_covered', capacity: 500, price: 8.0, is_active: 1});
            appendProductRow({name: 'Camion Scoperto', code: 'camion_open', capacity: 100, price: 15.0, is_active: 1});
            appendProductRow({name: 'Camion Coperto', code: 'camion_covered', capacity: 50, price: 20.0, is_active: 1});
        }
    }

    function addProductRow() {
        appendProductRow({});
    }

    function appendProductRow(data) {
        const tr = document.createElement('tr');
        tr.className = 'product-row';
        tr.style.background = data.delete ? 'rgba(239, 68, 68, 0.05)' : 'transparent';
        
        const isDelete = data.delete == 1 || data.delete == '1' || data.delete === true;
        
        tr.innerHTML = `
            ${data.id ? `<input type="hidden" name="products[${productIndex}][id]" value="${data.id}">` : ''}
            <input type="hidden" name="products[${productIndex}][delete]" class="delete-flag" value="${isDelete ? '1' : '0'}">
            
            <td style="padding: 8px;">
                <input type="text" name="products[${productIndex}][name]" class="pm-input" placeholder="Es. Auto Coperto" value="${data.name || ''}" style="width: 100%" required>
            </td>
            <td style="padding: 8px;">
                <input type="text" name="products[${productIndex}][code]" class="pm-input" placeholder="auto_covered" value="${data.code || ''}" style="width: 100%; font-family: var(--pm-mono)" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" name="products[${productIndex}][capacity]" class="pm-input capacity-input" placeholder="Es. 100" value="${data.capacity ?? 100}" min="0" style="width: 100px; font-family: var(--pm-mono)" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" name="products[${productIndex}][price]" class="pm-input" placeholder="0.00" value="${data.price ?? '0.00'}" step="0.01" min="0" style="width: 80px;" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" name="products[${productIndex}][sort_order]" class="pm-input" value="${data.sort_order ?? 0}" style="width: 60px;">
            </td>
            <td style="padding: 8px; text-align: center;">
                <input type="hidden" name="products[${productIndex}][is_active]" value="0">
                <input type="checkbox" name="products[${productIndex}][is_active]" value="1" class="active-flag" style="transform: scale(1.2)" ${((data.is_active ?? 1) == 1) ? 'checked' : ''}>
            </td>
            <td style="padding: 8px;">
                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="toggleDelete(this, ${data.id ? 'true' : 'false'})">
                    ${isDelete ? 'Annulla' : 'Elimina'}
                </button>
            </td>
        `;
        
        // Listeners for HUD
        const capInput = tr.querySelector('.capacity-input');
        const actFlag = tr.querySelector('.active-flag');
        
        capInput.addEventListener('input', updateHUD);
        actFlag.addEventListener('change', updateHUD);
        
        tbody.appendChild(tr);
        productIndex++;
        updateHUD();
    }

    function toggleDelete(btn, isExisting) {
        const tr = btn.closest('tr');
        const delInput = tr.querySelector('.delete-flag');
        const wasDeleted = delInput.value === '1';
        
        if (!wasDeleted && isExisting) {
            alert('NOTA: Eliminerai fisicamente questa categoria. Se essa vanta prenotazioni storiche il form bloccherà la cancellazione. In quel caso, consigliamo di rimuovere solo la spunta "Attivo".');
        }

        if (wasDeleted) {
            delInput.value = '0';
            tr.style.background = 'transparent';
            tr.style.opacity = '1';
            btn.textContent = 'Elimina';
        } else {
            delInput.value = '1';
            tr.style.background = 'rgba(239, 68, 68, 0.05)';
            tr.style.opacity = '0.5';
            btn.textContent = 'Annulla';
        }
        updateHUD();
    }

    function updateHUD() {
        const total = parseInt(totalSpotsInput.value) || 0;
        let allocated = 0;

        document.querySelectorAll('.product-row').forEach(tr => {
            const isDel = tr.querySelector('.delete-flag').value === '1';
            const isAct = tr.querySelector('.active-flag').checked;
            const cap = parseInt(tr.querySelector('.capacity-input').value) || 0;
            
            if (!isDel && isAct) {
                allocated += cap;
            }
        });

        const diff = total - allocated;

        hudTotal.textContent = total;
        hudAllocated.textContent = allocated;
        
        if (diff > 0) {
            hudDiff.textContent = `+${diff}`;
            hudPanel.style.borderColor = 'var(--pm-amber)';
            hudPanel.style.background = 'rgba(245, 158, 11, 0.05)';
            hudMsg.textContent = 'Hai capacità fisica non ancora allocata ai prodotti vendibili.';
            hudMsg.style.color = 'var(--pm-amber)';
            hudDiff.style.color = 'var(--pm-amber)';
            submitBtn.disabled = false;
            submitWarn.style.display = 'none';
        } else if (diff === 0) {
            hudDiff.textContent = 'OK';
            hudPanel.style.borderColor = 'var(--pm-green)';
            hudPanel.style.background = 'rgba(16, 185, 129, 0.05)';
            hudMsg.textContent = 'La configurazione è perfettamente allineata alla fisica.';
            hudMsg.style.color = 'var(--pm-green)';
            hudDiff.style.color = 'var(--pm-green)';
            submitBtn.disabled = false;
            submitWarn.style.display = 'none';
        } else {
            hudDiff.textContent = diff;
            hudPanel.style.borderColor = 'var(--pm-red)';
            hudPanel.style.background = 'rgba(239, 68, 68, 0.15)';
            hudMsg.innerHTML = '<strong>BLOCCO SISTEMA:</strong> La somma delle capacità supera la capienza del parcheggio!';
            hudMsg.style.color = 'var(--pm-red)';
            hudDiff.style.color = 'var(--pm-red)';
            submitBtn.disabled = true;
            submitWarn.style.display = 'block';
        }
    }
</script>

</x-app-layout>
