<x-guest-layout>
    <x-auth-session-status class="pm-flash-success" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="pm-form">
        @csrf

        <div class="pm-form-group">
            <label for="email" class="pm-label">Email</label>
            <input id="email" type="email" name="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   class="pm-input" />
            @error('email')
                <div class="pm-form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="pm-form-group">
            <label for="password" class="pm-label">Password</label>
            <input id="password" type="password" name="password"
                   required autocomplete="current-password"
                   class="pm-input" />
            @error('password')
                <div class="pm-form-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="pm-checkbox-group">
            <input id="remember_me" type="checkbox" name="remember" class="pm-checkbox" />
            <label for="remember_me" class="pm-checkbox-label">Ricordami</label>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:4px;">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="font-size:12px;color:var(--pm-text-muted);text-decoration:none;"
                   onmouseover="this.style.color='var(--pm-accent)'"
                   onmouseout="this.style.color='var(--pm-text-muted)'">
                    Password dimenticata?
                </a>
            @endif
            <button type="submit" class="pm-btn pm-btn-primary" style="flex:1;">
                Accedi
            </button>
        </div>
    </form>
</x-guest-layout>
