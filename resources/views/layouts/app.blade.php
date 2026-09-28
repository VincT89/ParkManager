<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="parking-context" content="{{ $activeParking?->id ?? 0 }}">
    <title>{{ config('app.name', 'ParkManager') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <div class="pm-layout" id="pm-layout">
        <div class="pm-sidebar-overlay" id="pm-sidebar-overlay" aria-hidden="true"></div>

        {{-- SIDEBAR --}}
        <aside class="pm-sidebar" id="pm-sidebar">

            <div class="pm-sidebar-header">
                <a href="{{ route('dashboard') }}" class="pm-sidebar-logo">
                    @if($activeManagement?->is_initial ?? true)
                    <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 48px; width: auto; object-fit: contain; flex-shrink: 0;" />
                    @endif
                    <span class="pm-sidebar-logo-text">{{ ($activeManagement?->is_initial ?? true) ? 'MODAUTO' : $activeManagement->name }}</span>
                </a>
                <button type="button" class="pm-sidebar-toggle" title="Riduci menu" aria-label="Riduci menu" aria-controls="pm-sidebar" aria-expanded="true">
                    <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                        <polyline points="9,3 4,7.5 9,12" />
                    </svg>
                </button>
                <button type="button" class="pm-sidebar-close pm-btn pm-btn-secondary">Chiudi menu</button>
            </div>

            <nav class="pm-sidebar-nav">

                <div class="pm-sidebar-section">Principale</div>

                <a href="{{ route('dashboard') }}"
                    class="pm-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="1" width="5.5" height="5.5" rx="1" />
                            <rect x="8.5" y="1" width="5.5" height="5.5" rx="1" />
                            <rect x="1" y="8.5" width="5.5" height="5.5" rx="1" />
                            <rect x="8.5" y="8.5" width="5.5" height="5.5" rx="1" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label">Dashboard</span>
                </a>

                <a href="{{ route('reservations.index') }}"
                    class="pm-sidebar-link {{ request()->routeIs('reservations.*') ? 'active' : '' }}"
                    title="Prenotazioni">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="2" width="13" height="11" rx="1.5" />
                            <line x1="1" y1="6" x2="14" y2="6" />
                            <line x1="5" y1="2" x2="5" y2="6" />
                            <line x1="10" y1="2" x2="10" y2="6" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label">Prenotazioni</span>
                </a>

                <a href="{{ route('calendar') }}"
                    class="pm-sidebar-link {{ request()->routeIs('calendar*') ? 'active' : '' }}" title="Calendario">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="2" width="13" height="11" rx="1.5" />
                            <line x1="1" y1="6" x2="14" y2="6" />
                            <line x1="5" y1="2" x2="5" y2="6" />
                            <line x1="10" y1="2" x2="10" y2="6" />
                            <line x1="4" y1="9" x2="7" y2="9" />
                            <line x1="4" y1="11.5" x2="9" y2="11.5" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label">Calendario</span>
                </a>

                <div class="pm-sidebar-section">Operativo</div>

                <a href="{{ route('availability-blocks.index') }}"
                    class="pm-sidebar-link {{ request()->routeIs('availability-blocks.*') ? 'active' : '' }}"
                    title="Blocchi">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="7.5" cy="7.5" r="6" />
                            <line x1="4.5" y1="4.5" x2="10.5" y2="10.5" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label">Blocchi</span>
                </a>

                <a href="{{ route('alerts') }}"
                    class="pm-sidebar-link {{ request()->routeIs('alerts*') ? 'active' : '' }}" title="Avvisi">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M7.5 1L1 13h13L7.5 1z" />
                            <line x1="7.5" y1="6" x2="7.5" y2="9" />
                            <circle cx="7.5" cy="11" r="0.5" fill="currentColor" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label"
                        style="display:flex;align-items:center;justify-content:space-between;flex:1">
                        Avvisi
                        @if (!empty($alertCount) && $alertCount > 0)
                            <span style="background:var(--pm-red);color:white;font-size:10px;font-family:var(--pm-mono);font-weight:500;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center;line-height:16px;">{{ $alertCount }}</span>
                        @endif
                    </span>
                </a>

                <div class="pm-sidebar-section">Report</div>

                <a href="{{ route('analytics') }}"
                    class="pm-sidebar-link {{ request()->routeIs('analytics*') ? 'active' : '' }}" title="Analisi">
                    <span class="pm-sidebar-link-icon">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <polyline points="1,11 5,6 8,9 11,4 14,7" />
                            <line x1="1" y1="13" x2="14" y2="13" />
                        </svg>
                    </span>
                    <span class="pm-sidebar-link-label">Analisi</span>
                </a>

                @if (auth()->user()->isAdmin())
                    <div class="pm-sidebar-section">Amministrazione</div>
                    <a href="{{ route('platforms.index') }}"
                        class="pm-sidebar-link {{ request()->routeIs('platforms.*') ? 'active' : '' }}"
                        title="Piattaforme">
                        <span class="pm-sidebar-link-icon">
                            <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="7.5" cy="7.5" r="2" />
                                <circle cx="7.5" cy="7.5" r="6" />
                                <line x1="7.5" y1="1.5" x2="7.5" y2="5.5" />
                                <line x1="7.5" y1="9.5" x2="7.5" y2="13.5" />
                                <line x1="1.5" y1="7.5" x2="5.5" y2="7.5" />
                                <line x1="9.5" y1="7.5" x2="13.5" y2="7.5" />
                            </svg>
                        </span>
                        <span class="pm-sidebar-link-label">Piattaforme</span>
                    </a>
                @endif

                @can('manage-parkings')
                    <a href="{{ route('parkings.index') }}"
                        class="pm-sidebar-link {{ request()->routeIs('parkings.*') ? 'active' : '' }}"
                        title="Parcheggi">
                        <span class="pm-sidebar-link-icon">
                            <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                                <circle cx="7.5" cy="7.5" r="2.5" />
                                <path d="M7.5 1v1.5m0 10V14m5.5-6.5h-1.5m-10 0H1m11-4.5l-1 1m-9 9l-1 1m10 0l-1-1m-9-9l-1-1" />
                            </svg>
                        </span>
                        <span class="pm-sidebar-link-label">Parcheggi</span>
                    </a>
                    <a href="{{ route('management.users.index') }}" class="pm-sidebar-link {{ request()->routeIs('management.users.*') ? 'active' : '' }}" title="Utenti">
                        <span class="pm-sidebar-link-icon" aria-hidden="true"><svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="7.5" cy="4" r="2.5"/><path d="M2 14v-2a5.5 5.5 0 0111 0v2"/></svg></span>
                        <span class="pm-sidebar-link-label">Utenti</span>
                    </a>
                    <a href="{{ route('management.activity') }}" class="pm-sidebar-link {{ request()->routeIs('management.activity') ? 'active' : '' }}" title="Registro attività">
                        <span class="pm-sidebar-link-icon" aria-hidden="true"><svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="1" width="11" height="13" rx="1"/><path d="M5 5h5M5 8h5M5 11h3"/></svg></span>
                        <span class="pm-sidebar-link-label">Registro attività</span>
                    </a>
                @endcan
            </nav>

            <div class="pm-sidebar-footer">
                <div class="pm-sidebar-user">
                    <a href="{{ route('admin.account.edit') }}"
                       style="display:flex;align-items:center;gap:10px;flex:1;text-decoration:none;color:inherit">

                        <div class="pm-avatar" style="flex-shrink:0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>

                        <div class="pm-sidebar-user-info">
                            <div class="pm-sidebar-user-name">{{ auth()->user()->name }}</div>
                            <div class="pm-sidebar-user-role">{{ ['admin' => 'Amministratore', 'staff' => 'Operatore'][auth()->user()->currentRole()?->value] ?? '' }}</div>
                        </div>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" style="flex-shrink:0">
                        @csrf
            @parkingContext
                        <button type="submit" class="pm-sidebar-logout" title="Esci">
                            Esci
                        </button>
                    </form>
                </div>
            </div>

        </aside>

        {{-- CONTENUTO --}}
        <div class="pm-content" id="pm-content">

            @if(isset($activeManagement))
                <div class="pm-parking-context">
                    @if($accessibleParkings->count() > 1)
                        <form method="POST" action="{{ route('parking.switch') }}" class="pm-parking-switch">
                            @csrf
            @parkingContext
                            <label for="active-parking">Parcheggio attivo</label>
                            <select id="active-parking" name="parking_id" class="pm-select">
                                @foreach($accessibleParkings as $selectableParking)
                                    <option value="{{ $selectableParking->id }}" @selected($activeParking?->id === $selectableParking->id)>{{ $selectableParking->name }}{{ $selectableParking->is_active ? '' : ' (disattivato)' }}</option>
                                @endforeach
                            </select>
                            <button class="pm-btn pm-btn-secondary" type="submit">Cambia</button>
                        </form>
                    @else
                        <span>Parcheggio attivo: <strong>{{ $activeParking?->name ?? $activeManagement->name }}</strong></span>
                    @endif
                </div>
            @endif
            @if (isset($header))
                <div class="pm-topbar {{ request()->routeIs('parkings.*', 'platforms.*', 'management.*') ? 'pm-settings-topbar' : '' }}">
                    <button type="button" class="pm-mobile-menu-btn" title="Apri menu" aria-label="Apri menu" aria-controls="pm-sidebar" aria-expanded="false">
                        <svg viewBox="0 0 15 15" fill="none" stroke="currentColor" stroke-width="1.5">
                            <line x1="2" y1="4" x2="13" y2="4"/>
                            <line x1="2" y1="7.5" x2="13" y2="7.5"/>
                            <line x1="2" y1="11" x2="13" y2="11"/>
                        </svg>
                    </button>
                    <div class="pm-topbar-content">{{ $header }}</div>
                </div>
            @endif

            <main class="pm-main {{ request()->routeIs('parkings.*', 'platforms.*', 'management.*') ? 'pm-settings-page' : '' }}">
                <div id="pm-request-error" class="pm-flash-error" role="alert" hidden></div>
                {{ $slot }}
            </main>

        </div>

    </div>

</body>

</html>
