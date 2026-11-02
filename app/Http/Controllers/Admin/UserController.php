<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Accès réservé au directeur : déjà garanti par le groupe role:directeur de web.php.

    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    // Création / modification se font en modale sur la page index.
    public function create()
    {
        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        return redirect()->route('admin.users.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username'],
            'password' => ['required', 'string', new StrongPassword],
            'role' => ['required', Rule::in(['directeur', 'employe'])],
        ], [
            'username.unique' => 'Cet identifiant est déjà utilisé.',
            'username.regex' => "L'identifiant ne peut contenir que des lettres, chiffres, point, tiret et underscore.",
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => $request->password, // haché par le cast 'hashed' du modèle
            'role' => $request->role,
            'status' => 'actif',
        ]);

        AuditLog::log(
            auth()->id(),
            'creation_utilisateur',
            'utilisateur',
            $user->id,
            "Utilisateur créé: {$user->username} ({$user->role})"
        );

        return redirect()->route('admin.users.index')->with('success', "Compte « {$user->username} » créé avec succès.");
    }

    public function update(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas modifier votre propre compte ici.');
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'username' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', Rule::in(['directeur', 'employe'])],
        ], [
            'username.unique' => 'Cet identifiant est déjà utilisé.',
            'username.regex' => "L'identifiant ne peut contenir que des lettres, chiffres, point, tiret et underscore.",
        ]);

        $fields = ['name', 'username', 'role'];
        $before = $user->only($fields);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
        ]);

        $changes = AuditLog::diff($before, $user->fresh()->only($fields));
        if ($changes) {
            AuditLog::log(
                auth()->id(),
                isset($changes['role']) ? 'modification_role' : 'modification_utilisateur',
                'utilisateur',
                $user->id,
                null,
                $changes
            );
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        }

        $oldStatus = $user->status;
        $newStatus = $oldStatus === 'actif' ? 'desactive' : 'actif';
        $user->update(['status' => $newStatus]);

        // Un compte désactivé ne doit pas rester connecté.
        if ($newStatus === 'desactive') {
            $this->revokeSessions($user);
        }

        AuditLog::log(
            auth()->id(),
            $newStatus === 'actif' ? 'reactivation_compte' : 'desactivation_compte',
            'utilisateur',
            $user->id,
            null,
            AuditLog::diff(['status' => $oldStatus], ['status' => $newStatus])
        );

        return redirect()->route('admin.users.index')->with(
            'success',
            $newStatus === 'actif' ? 'Compte réactivé avec succès.' : 'Compte désactivé avec succès.'
        );
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'string', new StrongPassword],
        ]);

        $user->update(['password' => $request->password]);

        // Pour un autre utilisateur, on coupe ses sessions ouvertes ; pas pour soi-même.
        if ($user->id !== auth()->id()) {
            $this->revokeSessions($user);
        }

        AuditLog::log(
            auth()->id(),
            'reinitialisation_mdp',
            'utilisateur',
            $user->id,
            "Mot de passe réinitialisé pour: {$user->username}"
        );

        return redirect()->route('admin.users.index')->with('success', 'Mot de passe mis à jour avec succès.');
    }

    private function revokeSessions(User $user): void
    {
        // Possible uniquement avec SESSION_DRIVER=database ; sinon la restriction
        // s'applique à la prochaine connexion (le login exige status = actif).
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
