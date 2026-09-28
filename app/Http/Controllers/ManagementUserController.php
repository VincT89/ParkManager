<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityRecorder;
use App\Tenancy\ParkingContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ManagementUserController extends Controller
{
    public function index(ParkingContext $context)
    {
        return view('management.users', ['members' => $context->management->users()->orderBy('name')->get()]);
    }

    public function store(Request $request, ParkingContext $context)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,staff'],
            'password' => ['nullable', 'confirmed', Password::min(12)],
        ]);
        DB::transaction(function () use ($data, $context) {
            $context->management->newQuery()->whereKey($context->management->id)->lockForUpdate()->firstOrFail();
            $user = User::where('email', strtolower($data['email']))->first();
            if ($user && ! $context->management->users()->whereKey($user->id)->exists()) {
                // Reusing an existing account must not reveal users of unrelated
                // managements to an administrator who guesses an email address.
                $administeredIds = auth()->user()->managements()->wherePivot('role', 'admin')->pluck('managements.id');
                $canReuse = $user->managements()->whereIn('managements.id', $administeredIds)->exists();
                if (! $canReuse) {
                    throw ValidationException::withMessages(['email' => 'Per abilitare questo account occorre un amministratore che ne gestisce già l’accesso.']);
                }
            }
            if (! $user) {
                if (empty($data['password'])) {
                    throw ValidationException::withMessages(['password' => 'Inserisci una password di almeno 12 caratteri per il nuovo account.']);
                }
                $user = User::create([
                    'name' => $data['name'], 'email' => strtolower($data['email']), 'password' => $data['password'],
                ]);
            }
            if ($context->management->users()->whereKey($user->id)->exists()) {
                throw ValidationException::withMessages(['email' => 'Questo utente ha già accesso alla gestione. Modifica il suo ruolo nell’elenco.']);
            }
            $context->management->users()->attach($user->id, ['role' => $data['role']]);
            app(ActivityRecorder::class)->record('access_granted', $user, ['role' => $data['role']]);
        });

        return back()->with('success', 'Accesso assegnato alla gestione corrente.');
    }

    public function update(Request $request, string $userId, ParkingContext $context)
    {
        $data = $request->validate(['role' => ['required', 'in:admin,staff']]);
        $this->changeAccess($userId, $data['role'], $context);

        return back()->with('success', 'Ruolo aggiornato per questa gestione.');
    }

    public function destroy(string $userId, ParkingContext $context)
    {
        $this->changeAccess($userId, null, $context);

        return redirect()->route('dashboard')->with('success', 'Accesso rimosso da questa gestione.');
    }

    private function changeAccess(string $userId, ?string $role, ParkingContext $context): void
    {
        DB::transaction(function () use ($userId, $role, $context) {
            $context->management->newQuery()->whereKey($context->management->id)->lockForUpdate()->firstOrFail();
            $user = $context->management->users()->whereKey($userId)->firstOrFail();
            if ($user->pivot->role === 'admin' && $role !== 'admin'
                && $context->management->users()->wherePivot('role', 'admin')->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'La gestione deve avere almeno un amministratore.']);
            }
            if ($role === null) {
                $context->management->users()->detach($user->id);
            } else {
                $context->management->users()->updateExistingPivot($user->id, ['role' => $role]);
            }
            app(ActivityRecorder::class)->record($role ? 'role_changed' : 'access_revoked', $user, ['role' => $role]);
        });
    }
}
