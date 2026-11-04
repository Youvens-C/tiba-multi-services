<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action_type',
        'entity_type',
        'entity_id',
        'entity_label',
        'details',
        'changes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'changes'    => 'array',
    ];

    // ========== RELATIONS ==========

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== SCOPES ==========

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ========== ÉCRITURE ==========

    /**
     * Enregistre une entrée de journal.
     * Rétrocompatible : les anciens appels AuditLog::log($uid, $action, $entity, $id, $details) fonctionnent
     * tels quels, et l'élément concerné est identifié automatiquement.
     *
     * @param array $changes  Résultat de AuditLog::diff($avant, $apres) (avant → après)
     */
    public static function log(
        int $userId,
        string $actionType,
        string $entityType,
        int $entityId,
        ?string $details = null,
        array $changes = [],
        ?string $entityLabel = null
    ): self {
        return self::create([
            'user_id'      => $userId,
            'action_type'  => $actionType,
            'entity_type'  => $entityType,
            'entity_id'    => $entityId,
            'entity_label' => $entityLabel ?? self::labelFor($entityType, $entityId),
            'details'      => $details,
            'changes'      => $changes ?: null,
        ]);
    }

    /**
     * Compare deux tableaux de valeurs et ne retourne que les champs modifiés.
     * Usage : $avant = $modele->only([...]); ...modification... ; $apres = $modele->fresh()->only([...]);
     *         AuditLog::log(..., changes: AuditLog::diff($avant, $apres));
     */
    public static function diff(array $before, array $after): array
    {
        $out = [];
        foreach ($after as $field => $new) {
            $old = $before[$field] ?? null;
            if (self::normalize($old) !== self::normalize($new)) {
                $out[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $out;
    }

    private static function normalize($value): string
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return is_numeric($value) ? (string) (float) $value : (is_null($value) ? '' : (string) $value);
    }

    // ========== IDENTIFICATION DE L'ÉLÉMENT ==========

    /** Texte lisible figé à la date de l'action. Ne doit jamais faire échouer l'action métier. */
    public static function labelFor(string $entityType, int $entityId): ?string
    {
        try {
            switch ($entityType) {
                case 'transaction':
                    $t = Transaction::find($entityId);
                    return $t ? sprintf(
                        'Transaction n°%d · %s · %s · %s %s · %s',
                        $t->id, ucfirst($t->service), $t->movement_label ?? $t->movement_type,
                        Fmt::num($t->amount), $t->currency, self::when($t->created_at)
                    ) : null;

                case 'dette':
                    $d = Debt::find($entityId);
                    return $d ? sprintf(
                        'Dette n°%d · %s · %s · %s',
                        $d->id, $d->debtor_name, Fmt::money($d->initial_amount, $d->currency), self::when($d->created_at)
                    ) : null;

                case 'paiement':
                case 'paiement_dette':
                    $p = DebtPayment::with('debt')->find($entityId);
                    return $p ? sprintf(
                        'Remboursement n°%d · %s · %s · %s',
                        $p->id, $p->debt->debtor_name ?? 'dette supprimée',
                        Fmt::money($p->amount_applied, $p->debt->currency ?? 'HTG'), self::when($p->created_at)
                    ) : null;

                case 'produit':
                    $a = Accessory::find($entityId);
                    return $a ? sprintf('Produit n°%d · %s', $a->id, $a->name) : null;

                case 'solde_ouverture':
                    $b = DailyOpeningBalance::find($entityId);
                    return $b ? sprintf('Solde d\'ouverture · %s · %s', ucfirst($b->service), $b->balance_date->format('d/m/Y')) : null;

                case 'utilisateur':
                    $u = User::find($entityId);
                    return $u ? sprintf('Utilisateur · %s (%s)', $u->name, $u->username) : null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return Str::of($entityType)->replace('_', ' ')->ucfirst() . ' n°' . $entityId;
    }

    private static function when($date): string
    {
        return $date ? $date->format('d/m/Y \à H:i') : '—';
    }

    // ========== LIBELLÉS D'AFFICHAGE ==========

    const ACTION_LABELS = [
        'connexion'                 => 'Connexion',
        'deconnexion'               => 'Déconnexion',
        'creation_transaction'      => 'Création de transaction',
        'modification_transaction'  => 'Modification de transaction',
        'annulation_transaction'    => 'Annulation de transaction',
        'creation_dette'            => 'Création de dette',
        'modification_dette'        => 'Modification de dette',
        'paiement_dette'            => 'Remboursement de dette',
        'annulation_paiement'       => 'Annulation de remboursement',
        'abandon_dette'             => 'Abandon de dette',
        'annulation_dette'          => 'Annulation de dette',
        'creation_produit'          => 'Création de produit',
        'modification_prix'         => 'Modification de prix',
        'changement_statut_produit' => 'Changement de statut produit',
        'vente_accessoire'          => 'Vente d\'accessoire',
        'ajustement_stock'          => 'Ajustement de stock',
        'creation_utilisateur'      => 'Création d\'utilisateur',
        'modification_utilisateur'  => 'Modification d\'utilisateur',
        'modification_role'         => 'Changement de rôle',
        'desactivation_compte'      => 'Désactivation de compte',
        'reactivation_compte'       => 'Réactivation de compte',
        'reinitialisation_mdp'      => 'Réinitialisation de mot de passe',
        'creation_solde_ouverture'  => 'Saisie du solde d\'ouverture',
        'modification_solde_ouverture' => 'Modification du solde d\'ouverture',
        'modification_vente_accessoire' => 'Modification de vente',
        'annulation_vente_accessoire' => 'Annulation de vente',
        'modification_produit'      => 'Modification de produit',
    ];

    const ENTITY_LABELS = [
        'transaction'    => 'Transaction',
        'dette'          => 'Dette',
        'paiement'       => 'Remboursement',
        'paiement_dette' => 'Remboursement',
        'produit'        => 'Produit',
        'utilisateur'    => 'Utilisateur',
        'solde_ouverture' => 'Solde d\'ouverture',
    ];

    const FIELD_LABELS = [
        'amount' => 'Montant', 'fee_client' => 'Frais client', 'fee_net' => 'Profit net',
        'service' => 'Service', 'movement_type' => 'Mouvement', 'client_phone' => 'Téléphone client',
        'status' => 'Statut', 'quantity' => 'Quantité', 'unit_price' => 'Prix unitaire',
        'total_amount' => 'Total', 'stock_quantity' => 'Stock', 'low_stock_threshold' => 'Seuil de stock bas',
        'name' => 'Nom', 'username' => 'Identifiant', 'description' => 'Description', 'role' => 'Rôle',
        'debtor_name' => 'Débiteur', 'reason' => 'Motif', 'initial_amount' => 'Montant initial',
        'remaining_balance' => 'Solde restant', 'currency' => 'Devise', 'opening_balance' => 'Solde d\'ouverture',
    ];

    const MONEY_FIELDS = [
        'amount', 'fee_client', 'fee_net', 'unit_price', 'total_amount',
        'initial_amount', 'remaining_balance', 'opening_balance',
    ];

    const VALUE_LABELS = [
        'directeur' => 'Directeur', 'employe' => 'Employé',
        'actif' => 'Actif', 'inactif' => 'Inactif', 'desactive' => 'Désactivé',
        'validee' => 'Validée', 'annulee' => 'Annulée',
        'active' => 'En cours', 'soldee' => 'Soldée', 'abandonnee' => 'Abandonnée',
        'moncash' => 'Moncash', 'natcash' => 'Natcash', 'zelle' => 'Zelle',
    ];

    public static function actionLabel(?string $action): string
    {
        return self::ACTION_LABELS[$action] ?? (string) Str::of((string) $action)->replace('_', ' ')->ucfirst();
    }

    public static function entityLabelOf(?string $entity): string
    {
        return self::ENTITY_LABELS[$entity] ?? (string) Str::of((string) $entity)->replace('_', ' ')->ucfirst();
    }

    public function getActionNameAttribute(): string
    {
        return self::actionLabel($this->action_type);
    }

    /** Couleur du texte de l'action : danger | warning | success | neutral */
    public function getActionToneAttribute(): string
    {
        $a = (string) $this->action_type;

        if (Str::startsWith($a, ['annulation', 'abandon', 'desactivation', 'reinitialisation'])) {
            return 'danger';
        }
        if (Str::startsWith($a, ['modification', 'ajustement', 'changement', 'solde'])) {
            return 'warning';
        }
        if (Str::startsWith($a, ['creation', 'vente', 'paiement', 'reactivation'])) {
            return 'success';
        }

        return 'neutral';
    }

    /** Lignes « Champ : ancien → nouveau » prêtes à afficher. */
    public function getChangeLinesAttribute(): array
    {
        $lines = [];
        // IMPORTANT : ne pas écrire $this->changes ici. Eloquent possède déjà une propriété interne
        // « changes » (utilisée par getChanges()) ; à l'intérieur du modèle elle masque la colonne.
        foreach ((array) $this->getAttribute('changes') as $field => $pair) {
            $lines[] = [
                'label' => self::FIELD_LABELS[$field] ?? (string) Str::of($field)->replace('_', ' ')->ucfirst(),
                'old'   => self::formatValue($field, $pair['old'] ?? null),
                'new'   => self::formatValue($field, $pair['new'] ?? null),
            ];
        }

        return $lines;
    }

    public static function formatValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($field === 'movement_type') {
            return Transaction::MOVEMENT_LABELS[$value] ?? (string) Str::of((string) $value)->replace('_', ' ')->ucfirst();
        }
        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return Fmt::num($value);
        }

        return self::VALUE_LABELS[$value] ?? (string) $value;
    }

    // ========== CONSTANTES EXISTANTES (conservées) ==========

    const ACTION_CANCEL_TRANSACTION = 'annulation_transaction';
    const ACTION_MODIFY_DEBT = 'modification_dette';
    const ACTION_CANCEL_PAYMENT = 'annulation_paiement';
    const ACTION_ABANDON_DEBT = 'abandon_dette';
    const ACTION_CANCEL_DEBT = 'annulation_dette';
    const ACTION_ADJUST_STOCK = 'ajustement_stock';
    const ACTION_MODIFY_PRICE = 'modification_prix';
    const ACTION_CREATE_USER = 'creation_utilisateur';
    const ACTION_MODIFY_ROLE = 'modification_role';
    const ACTION_DEACTIVATE_USER = 'desactivation_compte';
    const ACTION_REACTIVATE_USER = 'reactivation_compte';
    const ACTION_RESET_PASSWORD = 'reinitialisation_mdp';

    const ENTITY_TRANSACTION = 'transaction';
    const ENTITY_DEBT = 'dette';
    const ENTITY_DEBT_PAYMENT = 'paiement';
    const ENTITY_PRODUCT = 'produit';
    const ENTITY_USER = 'utilisateur';
}
