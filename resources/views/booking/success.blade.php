@extends('layouts.public')

@section('content')
    <div class="pm-card" style="text-align: center; padding: 48px 24px;">
        <div style="width: 64px; height: 64px; background: #ecfdf5; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 24px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
        </div>
        
        <h1 style="font-size: 24px; font-weight: 600; margin-bottom: 12px; color: var(--pm-text);">Prenotazione Ricevuta!</h1>
        <p style="color: var(--pm-text-muted); font-size: 16px; margin-bottom: 32px; max-width: 400px; margin-left: auto; margin-right: auto;">
            Grazie <strong>{{ $reservation->customer_name }}</strong>. La tua prenotazione è stata registrata con successo ed è in attesa di conferma.
        </p>

        <div style="background: var(--pm-bg); border: 1px solid var(--pm-border); border-radius: 8px; padding: 24px; max-width: 400px; margin: 0 auto; text-align: left;">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--pm-text-muted); margin-bottom: 16px; border-bottom: 1px solid var(--pm-border-light); padding-bottom: 8px;">Riepilogo Dettagli</div>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin-bottom: 12px; font-size: 14px;">
                <div style="color: var(--pm-text-muted);">Codice:</div>
                <div style="font-weight: 500;">{{ $reservation->external_id }}</div>
                
                <div style="color: var(--pm-text-muted);">Parcheggio:</div>
                <div style="font-weight: 500;">{{ $reservation->parkingListing->parking->name }}</div>
                
                <div style="color: var(--pm-text-muted);">Arrivo:</div>
                <div style="font-weight: 500;">{{ $reservation->starts_at->format('d/m/Y H:i') }}</div>
                
                <div style="color: var(--pm-text-muted);">Partenza:</div>
                <div style="font-weight: 500;">{{ $reservation->ends_at->format('d/m/Y H:i') }}</div>
                
                <div style="color: var(--pm-text-muted);">Veicolo:</div>
                <div style="font-weight: 500;">{{ $reservation->product?->name ?? 'Standard' }} ({{ $reservation->spots }} posti)</div>
                
                <div style="color: var(--pm-text-muted);">Targa:</div>
                <div style="font-weight: 500; text-transform: uppercase;">{{ $reservation->license_plate }}</div>
                
                <div style="color: var(--pm-text-muted);">Prezzo Totale:</div>
                <div style="font-weight: 600; color: #10b981;">{{ number_format($reservation->price, 2, ',', '.') }} €</div>
            </div>
        </div>

        <div style="margin-top: 32px;">
            <a href="{{ route('public.booking.form') }}" class="pm-btn pm-btn-secondary">Torna alla Home</a>
        </div>
    </div>
@endsection
