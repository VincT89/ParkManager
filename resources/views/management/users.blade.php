<x-app-layout>
    <x-slot name="header">
        <div><div class="pm-page-title">Utenti</div><div class="pm-page-subtitle">Accessi alla gestione {{ $activeManagement->name }}</div></div>
    </x-slot>
    <x-flash-message />

    <div class="pm-card" style="margin-bottom:24px">
        <h2 class="pm-card-title">Chi ha accesso</h2>
        <p class="pm-form-note" style="margin-top:8px">Gli utenti elencati possono accedere ai parcheggi di questa gestione. La rimozione dell’accesso vale solo per questa gestione.</p>
        <dl class="pm-role-help">
            <div><dt>Amministratore</dt><dd>Gestisce prenotazioni, impostazioni, piattaforme e accessi degli utenti.</dd></div>
            <div><dt>Operatore</dt><dd>Gestisce le prenotazioni e le operazioni quotidiane, senza modificare utenti e impostazioni dei parcheggi.</dd></div>
        </dl>
        <p class="pm-form-note pm-mobile-table-hint">Scorri la tabella per modificare i ruoli e gli accessi.</p>
        <div class="pm-table-wrapper" tabindex="0" role="region" aria-label="Utenti e accessi">
            <table class="pm-table pm-members-table">
                <colgroup><col style="width:18%"><col style="width:26%"><col style="width:36%"><col style="width:20%"></colgroup>
                <thead><tr><th>Nome</th><th>Email di accesso</th><th>Ruolo nella gestione</th><th>Accesso</th></tr></thead>
                <tbody>
                    @foreach($members as $member)
                        <tr>
                            <td>{{ $member->name }} @if($member->id === auth()->id())<span class="pm-form-note">(tu)</span>@endif</td>
                            <td>{{ $member->email }}</td>
                            <td>
                                <form method="POST" action="{{ route('management.users.update', $member->id) }}" class="pm-member-role-form">
                                    @csrf @parkingContext @method('PUT')
                                    <label class="sr-only" for="role-{{ $member->id }}">Ruolo di {{ $member->name }}</label>
                                    <select id="role-{{ $member->id }}" name="role" class="pm-select">
                                        <option value="admin" @selected($member->pivot->role === 'admin')>Amministratore</option>
                                        <option value="staff" @selected($member->pivot->role === 'staff')>Operatore</option>
                                    </select>
                                    <button class="pm-btn pm-btn-secondary" type="submit">Salva ruolo</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('management.users.destroy', $member->id) }}" onsubmit="return confirm('Rimuovere l’accesso di questo utente alla gestione?');">
                                    @csrf @parkingContext @method('DELETE')
                                    <button type="submit" class="pm-btn pm-btn-danger">Rimuovi accesso</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="pm-card">
        <h2 class="pm-card-title">Aggiungi un utente</h2>
        <p class="pm-form-note" style="margin:12px 0 20px">Per un nuovo account compila anche la password. Per riutilizzare un account di una gestione che amministri, lascia le password vuote: nome e password esistenti rimarranno invariati.</p>
        <form method="POST" action="{{ route('management.users.store') }}" class="pm-form" x-data="{ role: @js(old('role', 'staff')) }">
            @csrf @parkingContext
            <div class="pm-settings-grid">
                <div class="pm-form-group"><label for="member-name" class="pm-label">Nome dell’utente</label><input id="member-name" name="name" class="pm-input" value="{{ old('name') }}" maxlength="255" autocomplete="name" required></div>
                <div class="pm-form-group"><label for="member-email" class="pm-label">Email di accesso</label><input id="member-email" name="email" class="pm-input" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required></div>
            </div>
            <div class="pm-form-group">
                <label for="member-role" class="pm-label">Cosa potrà fare?</label>
                <select id="member-role" name="role" class="pm-select" x-model="role" aria-describedby="member-role-help">
                    <option value="staff" @selected(old('role') !== 'admin')>Operatore</option>
                    <option value="admin" @selected(old('role') === 'admin')>Amministratore</option>
                </select>
                <p id="member-role-help" class="pm-form-note" x-text="role === 'admin' ? 'Potrà amministrare questa gestione e collaborare con te, anche nella gestione degli utenti.' : 'Potrà lavorare sulle prenotazioni e sulle operazioni quotidiane.'">Potrà lavorare sulle prenotazioni e sulle operazioni quotidiane.</p>
            </div>
            <div class="pm-settings-grid">
                <div class="pm-form-group"><label for="member-password" class="pm-label">Password del nuovo account</label><input id="member-password" name="password" class="pm-input" type="password" minlength="12" autocomplete="new-password" aria-describedby="member-password-help"><p id="member-password-help" class="pm-form-note">Almeno 12 caratteri. Solo per un nuovo account.</p></div>
                <div class="pm-form-group"><label for="member-password-confirmation" class="pm-label">Ripeti la password</label><input id="member-password-confirmation" name="password_confirmation" class="pm-input" type="password" autocomplete="new-password"></div>
            </div>
            <div class="pm-form-actions"><button type="submit" class="pm-btn pm-btn-primary">Aggiungi utente</button></div>
        </form>
    </div>
</x-app-layout>
