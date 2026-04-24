<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ParkManager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--pm-bg);">

    <div style="width:100%;max-width:400px;padding:24px;">

        {{-- Logo --}}
        <div style="text-align:center;margin-bottom:32px;">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#4F7CFF,#7C5CFF);border-radius:12px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
                <svg width="24" height="24" viewBox="0 0 16 16" fill="white">
                    <path d="M2 2h12v2H2zm0 3h12v9H2zm2 2v5h3V7zm5 0v5h3V7z"/>
                </svg>
            </div>
            <div style="font-size:20px;font-weight:600;color:var(--pm-text);letter-spacing:-0.02em;">ParkManager</div>
            <div style="font-size:13px;color:var(--pm-text-muted);margin-top:4px;font-family:var(--pm-mono);">{{ config('app.name', 'ParkManager') }}</div>
        </div>

        {{-- Card --}}
        <div class="pm-card">
            {{ $slot }}
        </div>

    </div>

</body>
</html>
