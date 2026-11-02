<?php

/**
 * Messages de validation en français (règles utilisées par l'application).
 * Les règles absentes de ce fichier retombent sur l'anglais (fallback_locale).
 */
return [
    'required'         => 'Le champ :attribute est obligatoire.',
    'string'           => 'Le champ :attribute doit être un texte.',
    'numeric'          => 'Le champ :attribute doit être un nombre.',
    'integer'          => 'Le champ :attribute doit être un nombre entier.',
    'boolean'          => 'Le champ :attribute doit être vrai ou faux.',
    'array'            => 'Le champ :attribute est invalide.',
    'date'             => 'Le champ :attribute n\'est pas une date valide.',
    'after_or_equal'   => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'exists'           => 'La valeur choisie pour :attribute est invalide.',
    'in'               => 'La valeur choisie pour :attribute est invalide.',
    'unique'           => 'La valeur de :attribute est déjà utilisée.',
    'regex'            => 'Le format du champ :attribute est invalide.',
    'min' => [
        'numeric' => 'Le champ :attribute doit être supérieur ou égal à :min.',
        'string'  => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'numeric' => 'Le champ :attribute ne doit pas dépasser :max.',
        'string'  => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],

    'attributes' => [
        'date'              => 'date',
        'from'              => 'date de début',
        'to'                => 'date de fin',
        'amount'            => 'montant',
        'amount_paid'       => 'montant payé',
        'initial_amount'    => 'montant initial',
        'client_phone'      => 'numéro de téléphone',
        'service'           => 'service',
        'movement_type'     => 'mouvement',
        'quantity'          => 'quantité',
        'unit_price'        => 'prix unitaire',
        'stock_quantity'    => 'stock',
        'low_stock_threshold' => 'seuil de stock bas',
        'adjustment'        => 'ajustement',
        'name'              => 'nom',
        'username'          => 'identifiant',
        'password'          => 'mot de passe',
        'role'              => 'rôle',
        'debtor_name'       => 'nom du débiteur',
        'reason'            => 'motif',
        'currency'          => 'devise',
        'description'       => 'description',
        'user_id'           => 'utilisateur',
        'employee'          => 'employé',
        'opening_balance'   => 'solde d\'ouverture',
        'opening_balance.moncash' => 'solde Moncash',
        'opening_balance.natcash' => 'solde Natcash',
    ],
];
