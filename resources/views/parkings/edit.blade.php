<x-app-layout>
    @if(isset($parking))
        <div class="pm-card" style="padding:16px;margin-bottom:16px">
            <label for="public-booking-link">Collegamento per le prenotazioni di questo parcheggio</label>
            <input id="public-booking-link" class="pm-input" type="text" readonly value="{{ route('public.booking.form', ['parking' => $parking->public_booking_key]) }}">
        </div>
    @endif
    <x-slot name="header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div>
                <a href="{{ route('parkings.index') }}" class="pm-btn pm-btn-secondary pm-btn-sm pm-mb-8">
                    &larr; Torna ai parcheggi
                </a>
                <div class="pm-page-title">{{ $parking->name }}</div>
                <div class="pm-page-subtitle">Imposta posti, categorie, tariffe e posti riservati.</div>
            </div>
        </div>
    </x-slot>

    <div class="pm-animate">
        <x-flash-message />

        <!-- Form 1: Dati Generali Parcheggio -->
        <form action="{{ route('parkings.update', $parking) }}" method="POST">
            @csrf
            @parkingContext
            @method('PATCH')

            <div class="pm-card" style="margin-bottom: 24px;">
                <div class="pm-card-header" style="justify-content: space-between;">
                    <div class="pm-card-title">Dati del parcheggio</div>
                    <button type="submit" class="pm-btn pm-btn-primary pm-btn-sm">
                        Salva dati del parcheggio
                    </button>
                </div>
                
                <div class="pm-settings-grid">
                    <div class="pm-form-group">
                        <label class="pm-label" for="parking-name">Nome del parcheggio</label>
                        <input type="text" id="parking-name" name="name" class="pm-input" value="{{ old('name', $parking->name) }}" required>
                    </div>

                    <div class="pm-form-group">
                        <label class="pm-label" for="base_total_spots">Numero totale di posti</label>
                        <input type="number" id="base_total_spots" name="total_spots" class="pm-input" style="font-family: var(--pm-mono);" value="{{ old('total_spots', $parking->total_spots) }}" min="1" required>
                        <div style="font-size: 11px; color: var(--pm-text-muted); margin-top: 6px; line-height: 1.4;">
                            Il numero di veicoli che il parcheggio può ospitare. La somma dei posti delle categorie attive deve rientrare in questo totale.
                        </div>
                    </div>

                    <x-parking-capacity-field :value="old('capacity_mode', $parking->capacity_mode)" />

                    <div class="pm-form-group">
                        <label class="pm-label" for="parking-status">Stato del parcheggio</label>
                        <select id="parking-status" name="is_active" class="pm-select">
                            <option value="1" {{ old('is_active', $parking->is_active) ? 'selected' : '' }}>Attivo</option>
                            <option value="0" {{ old('is_active', $parking->is_active) ? '' : 'selected' }}>Disattivato</option>
                        </select>
                        <div style="font-size: 11px; color: var(--pm-amber); margin-top: 6px;">Un parcheggio disattivato non è disponibile per nuove prenotazioni.</div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form 2: Prodotti / Categorie Vendibili -->
        <form action="{{ route('parkings.products.upsert', $parking) }}" method="POST" id="config-form">
            @csrf
            @parkingContext
            @method('PUT')

            <div class="pm-card" style="margin-bottom: 24px;">
                <div class="pm-card-header" style="justify-content: space-between;">
                    <div class="pm-card-title">Categorie e tariffe</div>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="addProductRow()">Aggiungi categoria</button>
                    </div>
                </div>

                <p class="pm-form-note" style="margin-bottom:16px">Inserisci le categorie che vendi, i posti disponibili e le tariffe reali. Il codice identifica la categoria nei collegamenti con le piattaforme: usa lettere minuscole, numeri e trattini bassi.</p>
                <p class="pm-form-note pm-mobile-table-hint">Scorri la tabella per vedere tutti i campi.</p>
                <div class="pm-table-wrapper" tabindex="0" role="region" aria-label="Categorie e tariffe">
                    <table class="pm-products-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--pm-border); color: var(--pm-text-muted);">
                                <th style="padding: 12px 8px; font-weight: 500;">Nome</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Codice categoria</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Posti</th>
                                <th style="padding: 12px 8px; font-weight: 500;">Tariffa (€)</th>
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
                    <div class="pm-capacity-summary">
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px; font-size:14px;">Riepilogo dei posti</div>
                            <div id="live-validation-msg" style="font-size: 13px; color: var(--pm-text-muted);">...</div>
                        </div>
                        <div class="pm-capacity-totals">
                            <div style="text-align: right;">
                                <div style="font-size: 11px; text-transform: uppercase; color: var(--pm-text-dim);">Posti totali</div>
                                <div id="hud-total" style="font-size: 20px; font-weight: 600; font-family: var(--pm-mono);">0</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; text-transform: uppercase; color: var(--pm-text-dim);">Posti nelle categorie</div>
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
                        Riduci i posti delle categorie o aggiorna prima il totale del parcheggio.
                    </div>
                    <button type="submit" id="main-submit-btn" class="pm-btn pm-btn-primary" style="padding: 10px 24px;">
                        Salva categorie e tariffe
                    </button>
                </div>
            </div>
        </form>

        <!-- Form 3: Allocazioni (Sequenza B) -->
        <div class="pm-card" style="margin-bottom: 24px;">
            <div class="pm-card-header" style="justify-content: space-between;">
                <div class="pm-card-title">Posti riservati ad altri usi</div>
                @if($parking->capacity_mode === 'shared')
                <button type="button" class="pm-btn pm-btn-secondary pm-btn-sm" onclick="document.getElementById('new-allocation-form').style.display='block'">
                    Riserva dei posti
                </button>
                @endif
            </div>

            @if($parking->capacity_mode === 'per_product')
            <div class="pm-card pm-alert-row" style="margin-top: 16px; margin-bottom: 16px; background-color: var(--pm-bg); border: 1px solid var(--pm-border);">
                <div style="font-weight: 500; font-size: 14px;">I posti sono organizzati per categoria</div>
                <div style="font-size: 13px; color: var(--pm-text-muted); margin-top: 4px;">
                    Con i posti separati, ogni riserva va associata alla categoria interessata. Questo modulo riserva posti sul totale del parcheggio ed è disponibile solo con i posti condivisi.
                </div>
            </div>
            @endif

            <!-- New Allocation Form -->
            <div id="new-allocation-form" style="display: none; background: var(--pm-bg); padding: 16px; border: 1px solid var(--pm-border); border-radius: var(--pm-radius); margin-bottom: 16px;">
                <h4 style="margin-top: 0; margin-bottom: 12px; font-weight: 600; font-size: 14px;">Riserva posti per un periodo</h4>
                <form action="{{ route('parkings.allocations.store', $parking->id) }}" method="POST">
                    @csrf
            @parkingContext
                    <input type="hidden" name="parking_id" value="{{ $parking->id }}">
                    
                    <div class="pm-settings-grid">
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label" for="allocation-allocation_type">Destinazione dei posti</label>
                            <select id="allocation-allocation_type" name="allocation_type" class="pm-select" required>
                                <option value="rentcar">Autonoleggio</option>
                                <option value="internal_use">Uso Interno</option>
                                <option value="partner">Partner Commerciale</option>
                                <option value="maintenance">Manutenzione</option>
                                <option value="other">Altro</option>
                            </select>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label" for="allocation-spots">Numero di posti</label>
                            <input type="number" id="allocation-spots" name="spots" class="pm-input" min="1" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label" for="allocation-notes">Note o riferimento</label>
                            <input type="text" id="allocation-notes" name="notes" class="pm-input" placeholder="Es. Hertz furgoni">
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label" for="allocation-starts_at">Riservati dal</label>
                            <input type="datetime-local" id="allocation-starts_at" name="starts_at" class="pm-input" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label" for="allocation-ends_at">Fino a</label>
                            <input type="datetime-local" id="allocation-ends_at" name="ends_at" class="pm-input" required>
                        </div>
                        <div class="pm-form-group" style="margin-bottom: 0;">
                            <label class="pm-label pm-checkbox-group" style="gap:8px; cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" checked style="width:18px; height:18px;">
                                <span>Attiva Subito</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 16px; text-align: right;">
                        <button type="button" class="pm-btn pm-btn-secondary" onclick="document.getElementById('new-allocation-form').style.display='none'">Annulla</button>
                        <button type="submit" class="pm-btn pm-btn-primary">Salva posti riservati</button>
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
                            <tr style="border-bottom: 1px solid var(--pm-border); {{ !$alloc->is_active ? 'opacity: 0.5;' : '' }}">
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
                                    <div style="font-weight: 500;">{{ ['rentcar' => 'Autonoleggio', 'internal_use' => 'Uso interno', 'partner' => 'Partner commerciale', 'maintenance' => 'Manutenzione', 'other' => 'Altro'][$alloc->allocation_type] ?? $alloc->allocation_type }}</div>
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
                                    <form action="{{ route('parkings.allocations.destroy', ['parking' => $parking->id, 'allocation' => $alloc->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Rimuovere questa riserva di posti?');">
                                        @csrf
            @parkingContext
                                        @method('DELETE')
                                        <button type="submit" class="pm-btn pm-btn-danger pm-btn-sm" style="padding: 4px 8px; font-size: 11px;">Elimina</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="pm-text-muted" style="text-align: center; padding: 24px;">Nessun posto riservato ad altri usi.</td>
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

    function addProductRow() {
        appendProductRow({});
    }

    function escapeAttribute(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    }

    function appendProductRow(data) {
        const tr = document.createElement('tr');
        tr.className = 'product-row';
        tr.style.background = data.delete ? 'rgba(239, 68, 68, 0.05)' : 'transparent';
        
        const isDelete = data.delete == 1 || data.delete == '1' || data.delete === true;
        
        tr.innerHTML = `
            ${data.id ? `<input type="hidden" name="products[${productIndex}][id]" value="${escapeAttribute(data.id)}">` : ''}
            <input type="hidden" name="products[${productIndex}][delete]" class="delete-flag" value="${isDelete ? '1' : '0'}">
            
            <td style="padding: 8px;">
                <input type="text" aria-label="Nome categoria" name="products[${productIndex}][name]" class="pm-input" placeholder="Es. Auto Coperto" value="${escapeAttribute(data.name || '')}" style="width: 100%" required>
            </td>
            <td style="padding: 8px;">
                <input type="text" aria-label="Codice categoria" name="products[${productIndex}][code]" class="pm-input" placeholder="auto_covered" value="${escapeAttribute(data.code || '')}" style="width: 100%; font-family: var(--pm-mono)" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" aria-label="Posti della categoria" name="products[${productIndex}][capacity]" class="pm-input capacity-input" placeholder="Es. 100" value="${escapeAttribute(data.capacity ?? '')}" min="0" style="width: 100px; font-family: var(--pm-mono)" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" aria-label="Tariffa della categoria" name="products[${productIndex}][price]" class="pm-input" placeholder="0.00" value="${escapeAttribute(data.price ?? '')}" step="0.01" min="0" style="width: 80px;" required>
            </td>
            <td style="padding: 8px;">
                <input type="number" aria-label="Ordine di visualizzazione" name="products[${productIndex}][sort_order]" class="pm-input" value="${escapeAttribute(data.sort_order ?? 0)}" style="width: 60px;">
            </td>
            <td style="padding: 8px; text-align: center;">
                <input type="hidden" name="products[${productIndex}][is_active]" value="0">
                <input type="checkbox" aria-label="Categoria attiva" name="products[${productIndex}][is_active]" value="1" class="active-flag" style="transform: scale(1.2)" ${((data.is_active ?? 1) == 1) ? 'checked' : ''}>
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
            alert('La categoria sarà eliminata quando salvi. Se contiene prenotazioni, puoi soltanto disattivarla togliendo la spunta Attivo.');
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
            hudMsg.textContent = 'Una parte dei posti non è ancora assegnata alle categorie attive.';
            hudMsg.style.color = 'var(--pm-amber)';
            hudDiff.style.color = 'var(--pm-amber)';
            submitBtn.disabled = false;
            submitWarn.style.display = 'none';
        } else if (diff === 0) {
            hudDiff.textContent = 'OK';
            hudPanel.style.borderColor = 'var(--pm-green)';
            hudPanel.style.background = 'rgba(16, 185, 129, 0.05)';
            hudMsg.textContent = 'Tutti i posti sono assegnati alle categorie attive.';
            hudMsg.style.color = 'var(--pm-green)';
            hudDiff.style.color = 'var(--pm-green)';
            submitBtn.disabled = false;
            submitWarn.style.display = 'none';
        } else {
            hudDiff.textContent = diff;
            hudPanel.style.borderColor = 'var(--pm-red)';
            hudPanel.style.background = 'rgba(239, 68, 68, 0.15)';
            hudMsg.innerHTML = 'La somma dei posti delle categorie supera il totale del parcheggio.';
            hudMsg.style.color = 'var(--pm-red)';
            hudDiff.style.color = 'var(--pm-red)';
            submitBtn.disabled = true;
            submitWarn.style.display = 'block';
        }
    }
</script>

</x-app-layout>
