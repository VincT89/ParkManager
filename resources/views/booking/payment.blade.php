@extends('layouts.public')

@section('content')
    <style>
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .pm-card-payment {
            max-width: 560px;
            margin: 0 auto;
        }
    </style>

    <div class="pm-card pm-card-payment">
        <div class="pm-card-header" style="text-align: center; border-bottom: 1px solid var(--pm-border); padding-bottom: 20px;">
            <h2 class="pm-card-title">Completa il tuo ordine</h2>
            <div style="font-size: 14px; color: var(--pm-text-muted); margin-top: 6px;">
                Prenotazione <span style="background: var(--pm-bg-soft); color: var(--pm-text-dark); padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: 600;">{{ $reservation->external_id }}</span>
            </div>
        </div>

        <div style="padding: 24px 32px;">
            
            <!-- Timer Alert -->
            <div id="timer-container" style="background: #fffbeb; border: 1px solid #fef3c7; color: #b45309; padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; transition: all 0.3s;">
                <div style="display: flex; align-items: center; font-size: 14px; font-weight: 500;">
                    <svg class="animate-pulse" style="width: 18px; height: 18px; margin-right: 8px; color: #f59e0b;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Tempo rimanente per il pagamento
                </div>
                <div id="countdown" style="font-family: monospace; font-weight: 700; font-size: 18px;">15:00</div>
            </div>

            <div id="expired-alert" style="display: none; background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; padding: 24px; border-radius: 8px; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 24px; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                    <svg style="width: 24px; height: 24px; margin-right: 8px; color: #ef4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span style="font-weight: 600; font-size: 16px;">Tempo scaduto</span>
                </div>
                <p style="font-size: 14px; margin-bottom: 16px; opacity: 0.9;">Questa prenotazione è stata annullata automaticamente.</p>
                
                <a href="{{ route('public.booking.form', ['fresh' => time()]) }}" style="display: inline-flex; align-items: center; background: white; color: #334155; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 6px; font-weight: 500; font-size: 14px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <svg style="width: 16px; height: 16px; margin-right: 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Rifai la prenotazione
                </a>
            </div>

            <!-- Summary -->
            <div style="background: var(--pm-bg-soft); border: 1px solid var(--pm-border); border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--pm-border); padding-bottom: 16px; margin-bottom: 16px;">
                    <span style="color: var(--pm-text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Totale da pagare</span>
                    <span style="font-size: 28px; font-weight: 700; color: var(--pm-text-dark);">€ {{ number_format($reservation->price, 2, ',', '.') }}</span>
                </div>
                <div style="font-size: 13px; color: var(--pm-text-muted); text-align: center; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 16px; height: 16px; color: #16a34a; margin-right: 6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    I pagamenti sono sicuri e criptati
                </div>
            </div>

            <!-- Error Container -->
            <div id="error-container" style="display: none; background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 24px;"></div>

            <!-- Payment Options -->
            <div id="payment-options">
                
                <!-- Stripe Button -->
                <form method="POST" action="{{ route('public.booking.stripe.checkout', $reservation->external_id) }}" id="stripe-form">
                    @csrf
                    <button type="submit" id="stripe-btn" class="pm-btn pm-btn-primary" style="width: 100%; padding: 14px; font-size: 16px; display: flex; justify-content: center; align-items: center; position: relative;">
                        <span id="stripe-spinner" style="display: none; position: absolute; left: 16px;">
                            <svg class="animate-spin" style="height: 20px; width: 20px; color: white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                        <svg style="width: 20px; height: 20px; margin-right: 8px; opacity: 0.9;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        Paga con Carta di Credito
                    </button>
                </form>

                <div style="display: flex; align-items: center; margin: 24px 0;">
                    <div style="flex-grow: 1; border-top: 1px solid var(--pm-border);"></div>
                    <span style="padding: 0 16px; color: var(--pm-text-muted); font-size: 13px;">oppure</span>
                    <div style="flex-grow: 1; border-top: 1px solid var(--pm-border);"></div>
                </div>

                <!-- PayPal Button Container -->
                @if($paypalClientId)
                    <div id="paypal-button-container" style="min-height: 50px; position: relative; z-index: 0;">
                        <div id="paypal-overlay" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.7); z-index: 10; border-radius: 8px; cursor: not-allowed; align-items: center; justify-content: center;"></div>
                    </div>
                @else
                    <div style="text-align: center; color: var(--pm-text-muted); font-size: 13px; padding: 12px; border: 1px dashed var(--pm-border); border-radius: 8px;">
                        ⚠️ PayPal non è configurato. Inserisci <strong style="font-family: monospace;">PAYPAL_CLIENT_ID</strong> nel file .env
                    </div>
                @endif
                
            </div>
        </div>
        
        <div style="background: var(--pm-bg-soft); border-top: 1px solid var(--pm-border); padding: 16px; text-align: center;">
            <a href="#" style="font-size: 13px; font-weight: 500; color: var(--pm-text-muted); text-decoration: none;">Hai bisogno di assistenza?</a>
        </div>
    </div>

    <!-- JS SDKs -->
    @if($paypalClientId)
        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ config('payments.paypal.currency', 'EUR') }}&disable-funding=card"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Elements
            const stripeForm = document.getElementById('stripe-form');
            const stripeBtn = document.getElementById('stripe-btn');
            const stripeSpinner = document.getElementById('stripe-spinner');
            const errorContainer = document.getElementById('error-container');
            const timerContainer = document.getElementById('timer-container');
            const expiredAlert = document.getElementById('expired-alert');
            const countdownEl = document.getElementById('countdown');
            const paymentOptions = document.getElementById('payment-options');
            const paypalOverlay = document.getElementById('paypal-overlay');

            // Timer Logic
            const expiresAtTimestamp = {{ $reservation->expires_at ? $reservation->expires_at->timestamp * 1000 : 0 }};
            
            function updateTimer() {
                if (!expiresAtTimestamp) return;

                const now = new Date().getTime();
                const distance = expiresAtTimestamp - now;

                if (distance <= 0) {
                    clearInterval(timerInterval);
                    expireUI();
                    return;
                }

                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                countdownEl.innerHTML = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                
                if (distance < 60000) { // last minute
                    countdownEl.style.color = '#dc2626'; // red-600
                    countdownEl.classList.add('animate-pulse');
                }
            }

            function expireUI() {
                timerContainer.style.display = 'none';
                expiredAlert.style.display = 'flex';
                
                // Disable Stripe
                stripeBtn.disabled = true;
                stripeBtn.style.opacity = '0.5';
                stripeBtn.style.cursor = 'not-allowed';
                
                // Disable PayPal visually (prevent clicks)
                if (paypalOverlay) {
                    paypalOverlay.style.display = 'flex';
                }
            }

            const timerInterval = setInterval(updateTimer, 1000);
            updateTimer();

            // Stripe Loading State
            stripeForm.addEventListener('submit', () => {
                stripeBtn.disabled = true;
                stripeBtn.style.opacity = '0.9';
                stripeBtn.style.cursor = 'wait';
                stripeSpinner.style.display = 'block';
                errorContainer.style.display = 'none';
            });

            function showError(message) {
                errorContainer.textContent = message;
                errorContainer.style.display = 'block';
                
                // Reset Stripe btn just in case
                stripeBtn.disabled = false;
                stripeBtn.style.opacity = '1';
                stripeBtn.style.cursor = 'pointer';
                stripeSpinner.style.display = 'none';
            }

            // PayPal Setup
            if (typeof paypal !== 'undefined') {
                paypal.Buttons({
                    fundingSource: paypal.FUNDING.PAYPAL,
                    style: {
                        color: 'blue',
                        shape: 'rect',
                        label: 'pay',
                        height: 50
                    },
                    
                    createOrder: async function() {
                        errorContainer.style.display = 'none';
                        
                        try {
                            const response = await fetch("{{ route('public.booking.paypal.order', $reservation->external_id) }}", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                    "Accept": "application/json"
                                }
                            });

                            if (!response.ok) throw new Error('Errore di connessione al server.');
                            
                            const order = await response.json();
                            if (!order.id) throw new Error('Ordine non valido restituito dal server.');
                            
                            return order.id;
                        } catch (error) {
                            showError('Impossibile avviare PayPal. Riprova tra poco.');
                            throw error;
                        }
                    },

                    onApprove: async function(data) {
                        // Show loading state over PayPal container during capture
                        if (paypalOverlay) {
                            paypalOverlay.style.display = 'flex';
                            paypalOverlay.style.background = 'rgba(255,255,255,0.8)';
                            paypalOverlay.innerHTML = '<svg class="animate-spin" style="height: 24px; width: 24px; color: #2563eb;" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                        }

                        try {
                            const response = await fetch("{{ route('public.booking.paypal.capture', $reservation->external_id) }}", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                                    "Accept": "application/json"
                                },
                                body: JSON.stringify({ order_id: data.orderID })
                            });

                            const result = await response.json();

                            if (!response.ok || !result.redirect_url) {
                                throw new Error(result.message || 'Transazione fallita o respinta.');
                            }

                            window.location.href = result.redirect_url;
                        } catch (error) {
                            if (paypalOverlay) {
                                paypalOverlay.style.display = 'none';
                                paypalOverlay.innerHTML = '';
                            }
                            showError('Cattura del pagamento fallita. Il tuo conto non è stato addebitato. (' + error.message + ')');
                        }
                    },
                    
                    onError: function (err) {
                        showError('Si è verificato un errore con PayPal. Riprova con un altro metodo.');
                        console.error(err);
                    },
                    
                    onCancel: function (data) {
                        showError('Pagamento PayPal annullato.');
                    }
                }).render('#paypal-button-container');
            }
        });
    </script>
@endsection
