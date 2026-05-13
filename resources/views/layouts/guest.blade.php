<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ParkManager') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo_blue.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--pm-bg);">

    <div style="width:100%;max-width:400px;padding:24px;">

        {{-- Logo --}}
        <div style="text-align:center;margin-bottom:32px;">
            <img src="{{ asset('img/logo_blue.png') }}" alt="Logo" style="height: 96px; width: auto; object-fit: contain; margin-bottom: 16px;" />
            <div style="font-size:20px;font-weight:600;color:var(--pm-text);letter-spacing:-0.02em;">MODAUTO</div>
        </div>

        {{-- Card --}}
        <div class="pm-card">
            {{ $slot }}
        </div>

    </div>

</body>
</html>
