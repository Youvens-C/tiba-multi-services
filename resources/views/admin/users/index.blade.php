@extends('layouts.app')

@section('title', 'Utilisateurs')

@push('styles')
<style>
    .pwd-rules { list-style: none; padding: 0; margin: .6rem 0; font-size: .8rem; color: var(--text-muted); }
    .pwd-rules li::before { content: "○ "; }
    .pwd-rules li.ok { color: var(--green-dark); }
    .pwd-rules li.ok::before { content: "✓ "; font-weight: 700; }
    .user-actions { display: flex; gap: .4rem; justify-content: flex-end; flex-wrap: nowrap; }
</style>
@endpush

@section('content')

    <div class="panel">
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="panel-title mb-0">Comptes utilisateurs</div>
            <button type="button" class="btn-tiba-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-plus-lg me-1"></i> Nouvel utilisateur
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-tiba align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Identifiant</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        @php $isSelf = $u->id === auth()->id(); @endphp
                        <tr>
                            <td class="fw-semibold">
                                {{ $u->name }}
                                @if($isSelf) <span class="text-muted fw-normal small">(vous)</span> @endif
                            </td>
                            <td class="text-muted">{{ $u->username }}</td>
                            <td>{{ $u->role === 'directeur' ? 'Directeur' : 'Employé' }}</td>
                            <td>
                                @if($u->status === 'actif')
                                    <span class="status-validee">Actif</span>
                                @else
                                    <span class="status-inactif">Désactivé</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $u->last_login_at ? $u->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</td>
                            <td>
                                <div class="user-actions">
                                    @unless($isSelf)
                                        <button type="button" class="btn-tiba-secondary js-edit-user" title="Modifier"
                                                style="padding:.3rem .7rem;font-size:.8rem;"
                                                data-url="{{ route('admin.users.update', $u) }}"
                                                data-name="{{ $u->name }}" data-username="{{ $u->username }}" data-role="{{ $u->role }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    @endunless
                                    <button type="button" class="btn-tiba-secondary js-reset-pwd" title="Réinitialiser le mot de passe"
                                            style="padding:.3rem .7rem;font-size:.8rem;"
                                            data-url="{{ route('admin.users.reset-password', $u) }}" data-name="{{ $u->name }}">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    @unless($isSelf)
                                        <form method="POST" action="{{ route('admin.users.toggle', $u) }}"
                                              class="{{ $u->status === 'actif' ? 'js-user-toggle' : '' }}" data-name="{{ $u->name }}">
                                            @csrf
                                            <button type="submit" title="{{ $u->status === 'actif' ? 'Désactiver' : 'Réactiver' }}"
                                                    class="{{ $u->status === 'actif' ? 'btn-tiba-danger' : 'btn-tiba-secondary' }}"
                                                    style="padding:.3rem .7rem;font-size:.8rem;">
                                                <i class="bi bi-{{ $u->status === 'actif' ? 'person-slash' : 'person-check' }}"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Modale : nouvel utilisateur ───────────────────────────────────── --}}
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" action="{{ route('admin.users.store') }}" class="js-pwd-form">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Nouvel utilisateur</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="name" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Identifiant de connexion</label>
                            <input type="text" name="username" class="form-control w-100" autocapitalize="none" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Rôle</label>
                            <select name="role" class="form-select w-100" required>
                                <option value="employe" selected>Employé</option>
                                <option value="directeur">Directeur</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Mot de passe</label>
                            @include('admin.users._password_field', ['prefix' => 'create'])
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary" disabled>Créer le compte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : modifier un utilisateur ──────────────────────────────── --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="editUserForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <h5 class="mb-3">Modifier l'utilisateur</h5>
                        <div class="mb-3">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="name" id="editUserName" class="form-control w-100" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Identifiant de connexion</label>
                            <input type="text" name="username" id="editUserUsername" class="form-control w-100" autocapitalize="none" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Rôle</label>
                            <select name="role" id="editUserRole" class="form-select w-100" required>
                                <option value="employe">Employé</option>
                                <option value="directeur">Directeur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Modale : réinitialiser le mot de passe ────────────────────────── --}}
    <div class="modal fade" id="resetPwdModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:16px; border:none;">
                <form method="POST" id="resetPwdForm" class="js-pwd-form">
                    @csrf
                    <div class="modal-body p-4">
                        <h5 class="mb-1">Réinitialiser le mot de passe</h5>
                        <p class="text-muted small mb-3" id="resetPwdName"></p>
                        @include('admin.users._password_field', ['prefix' => 'reset'])
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn-tiba-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-tiba-primary" disabled>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('js/users.js') }}"></script>
@endpush
