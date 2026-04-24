<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ParkManager') }} - Prenotazione Pubblica</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pm-public" style="min-height:100vh;">

    {{-- Topbar Branding --}}
    <div style="background: #fff; border-bottom: 3px solid var(--pm-public-accent);">
        <div style="max-width: 800px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; gap: 12px; height: 64px;">
            <div style="background: #1C1F2E; color: var(--pm-public-accent); font-weight: 700; font-size: 15px; letter-spacing: 1px; padding: 6px 13px; border-radius: 5px; flex-shrink: 0;">
                MODAUTO
            </div>
            <div style="width: 1px; height: 26px; background: var(--pm-border); flex-shrink: 0;"></div>
            <div style="font-size: 15px; font-weight: 600; color: var(--pm-text);">
                Prenotazione {{ config('app.name', 'Parcheggio') }}
            </div>
        </div>
    </div>

    {{-- HERO HEADER --}}
    <div style="background: #1C1F2E; padding: 28px 24px 26px; position: relative; overflow: hidden;">
        {{-- Pattern decorativo --}}
        <div style="position:absolute; right:0; top:0; bottom:0; width:38%;
                    background: repeating-linear-gradient(45deg, transparent, transparent 9px,
                    rgba(249,96,32,0.07) 9px, rgba(249,96,32,0.07) 18px);
                    pointer-events:none;"></div>
        <div style="max-width: 800px; margin: 0 auto; position: relative; z-index: 1;">
            <div style="font-size: 10px; font-weight: 500; letter-spacing: 1.8px; text-transform: uppercase; color: var(--pm-public-accent); margin-bottom: 7px;">
                Parcheggio con navetta · Aeroporto di Bari
            </div>
            <div style="font-size: 22px; font-weight: 600; color: #fff; margin-bottom: 8px; line-height: 1.25;">
                Prenota il tuo <span style="color: var(--pm-public-accent);">parcheggio</span>
            </div>
            <div style="font-size: 12px; color: rgba(255,255,255,0.45); display: flex; gap: 18px; flex-wrap: wrap;">
                <span style="display:flex; align-items:center; gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Aperto 24h su 24h
                </span>
                <span style="display:flex; align-items:center; gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    Navetta gratuita inclusa
                </span>
                <span style="display:flex; align-items:center; gap:5px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Parcheggio custodito
                </span>
            </div>
        </div>
    </div>

    {{-- Contenuto principale --}}
    <div style="max-width: 800px; margin: 32px auto; padding: 0 24px;">
        @yield('content')
    </div>

</body>
</html>
