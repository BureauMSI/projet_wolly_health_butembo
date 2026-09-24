<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'date' => 'Le champ :attribute n est pas une date valide.',
    'image' => 'Le champ :attribute doit être une image.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'in' => 'La valeur du champ :attribute est invalide.',
    'exists' => 'La valeur du champ :attribute est invalide.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'max' => [
        'array' => 'Le champ :attribute ne doit pas avoir plus de :max éléments.',
        'file' => 'Le champ :attribute ne doit pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne doit pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'array' => 'Le champ :attribute doit avoir au moins :min éléments.',
        'file' => 'Le champ :attribute doit faire au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'password' => [
        'letters' => 'Le champ :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le champ :attribute doit contenir des majuscules et des minuscules.',
        'numbers' => 'Le champ :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le champ :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute est trop courant. Choisissez-en un autre.',
        'min' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
];
