<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="pm-page-title">Account</div>
            <div class="pm-page-subtitle">gestione email e password di accesso</div>
        </div>
    </x-slot>

    <x-flash-message />

    <div class="pm-grid-2">
        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-title">Email accesso</div>
            </div>

            <form method="POST" action="{{ route('admin.account.email.update') }}" class="pm-form">
                @csrf
                @method('PUT')

                <div class="pm-field">
                    <label class="pm-label" for="email">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="pm-input"
                        value="{{ old('email', $user->email) }}"
                        required
                    >

                    @error('email')
                        <div class="pm-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="pm-btn pm-btn-primary">
                    Salva email
                </button>
            </form>
        </div>

        <div class="pm-card">
            <div class="pm-card-header">
                <div class="pm-card-title">Password accesso</div>
            </div>

            <form method="POST" action="{{ route('admin.account.password.update') }}" class="pm-form">
                @csrf
                @method('PUT')

                <div class="pm-field">
                    <label class="pm-label" for="current_password">Password attuale</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        class="pm-input"
                        required
                    >

                    @error('current_password')
                        <div class="pm-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pm-field">
                    <label class="pm-label" for="password">Nuova password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="pm-input"
                        required
                    >

                    @error('password')
                        <div class="pm-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="pm-field">
                    <label class="pm-label" for="password_confirmation">Conferma nuova password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="pm-input"
                        required
                    >
                </div>

                <button type="submit" class="pm-btn pm-btn-primary">
                    Salva password
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
